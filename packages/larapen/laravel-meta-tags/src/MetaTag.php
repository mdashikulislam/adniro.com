<?php

namespace Larapen\LaravelMetaTags;

use Illuminate\Http\Request;
use InvalidArgumentException;

class MetaTag
{
	private const DEFAULT_LIMITS = [
		'title'       => 60,
		'description' => 160,
		'keywords'    => 255,
	];
	
	private const SUPPORTED_OG_TAGS = [
		'title', 'description', 'type', 'image', 'url', 'audio',
		'determiner', 'locale', 'site_name', 'video',
	];
	
	private const SUPPORTED_TWITTER_TAGS = [
		'card', 'site', 'title', 'description', 'creator', 'image:src', 'domain',
	];
	
	private Request $request;
	private array $config;
	private string $defaultLocale;
	private array $metas = [];
	
	public function __construct(Request $request, array $config = [], string $defaultLocale = 'en')
	{
		$this->request = $request;
		$this->config = $this->normalizeConfig($config);
		$this->defaultLocale = $defaultLocale;
		
		$this->initializeDefaults();
		$this->processLocalesCallback();
	}
	
	/**
	 * Normalize and validate configuration
	 */
	private function normalizeConfig(array $config): array
	{
		return array_merge([
			'title'             => '',
			'locales'           => [],
			'title_limit'       => self::DEFAULT_LIMITS['title'],
			'description_limit' => self::DEFAULT_LIMITS['description'],
			'keywords_limit'    => self::DEFAULT_LIMITS['keywords'],
			'open_graph'        => [],
			'twitter'           => [],
			'locale_url'        => '[scheme]://[locale][host][uri]',
		], $config);
	}
	
	/**
	 * Initialize default meta values
	 */
	private function initializeDefaults(): void
	{
		$this->set('title', $this->config['title']);
		$this->set('url', $this->request->url());
	}
	
	/**
	 * Process locales if it's a callback
	 */
	private function processLocalesCallback(): void
	{
		$locales = $this->config['locales'];
		
		if (is_callable($locales)) {
			$this->setLocales($locales());
		}
	}
	
	/**
	 * Set supported locales for the application
	 */
	public function setLocales(array $locales = []): self
	{
		$this->config['locales'] = array_filter($locales, 'is_string');
		
		return $this;
	}
	
	/**
	 * Get a meta value with optional default
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		return $this->metas[$key] ?? $default;
	}
	
	/**
	 * Set a meta value with automatic processing
	 */
	public function set(string $key, ?string $value = null): ?string
	{
		if ($key === '') {
			throw new InvalidArgumentException('Meta key cannot be empty');
		}
		
		$normalizedValue = $this->normalizeMetaTagValue($value);
		$processedValue = $this->processMetaValue($key, $normalizedValue);
		
		$this->metas[$key] = $processedValue;
		
		return $processedValue;
	}
	
	/**
	 * Process meta value using custom setter or default cutting logic
	 */
	private function processMetaValue(string $key, ?string $value): ?string
	{
		$customMethod = 'set' . ucfirst($key);
		
		if (method_exists($this, $customMethod)) {
			return $this->$customMethod($value);
		}
		
		return $this->cutText($value, $key);
	}
	
	/**
	 * Custom setter for title with site name appending
	 */
	private function setTitle(?string $value): ?string
	{
		if (empty($value)) {
			return null;
		}
		
		$siteName = $this->config['site_name'] ?? null;
		$titleLimit = $this->config['title_limit'] ?? self::DEFAULT_LIMITS['title'];
		
		if ($siteName) {
			$suffix = ' - ' . $siteName;
			$availableLength = $titleLimit - mb_strlen($suffix);
			$truncatedTitle = $this->cutText($value, $availableLength);
			
			return $truncatedTitle . $suffix;
		}
		
		return $this->cutText($value, $titleLimit);
	}
	
	/**
	 * Generate a single meta tag
	 */
	public function tag(string $key, ?string $value = null): string
	{
		if (empty($key)) {
			return '';
		}
		
		$content = $value ?? $this->get($key, '');
		
		if (empty($content)) {
			return '';
		}
		
		return $this->buildMetaTag([
			'name'     => $key,
			'property' => $key,
			'content'  => $content,
		]);
	}
	
	/**
	 * Generate canonical and alternate language tags
	 */
	public function canonical(): string
	{
		$currentUrl = $this->request->url();
		$tags = [];
		
		// Canonical tag
		$tags[] = $this->buildLinkTag([
			'rel'  => 'canonical',
			'href' => $currentUrl,
		]);
		
		// Alternate language tags
		foreach ($this->config['locales'] as $locale) {
			if (is_string($locale)) {
				$localizedUrl = $this->generateLocalizedUrl($locale);
				$tags[] = $this->buildLinkTag([
					'rel'      => 'alternate',
					'hreflang' => $locale,
					'href'     => $localizedUrl,
				]);
			}
		}
		
		return implode("\n    ", $tags);
	}
	
	/**
	 * Generate Open Graph meta tags
	 */
	public function openGraph(): string
	{
		$tags = [];
		
		// Always include URL
		$tags[] = $this->buildMetaTag([
			'property' => 'og:url',
			'content'  => $this->request->url(),
		]);
		
		foreach (self::SUPPORTED_OG_TAGS as $tag) {
			$value = $this->config['open_graph'][$tag] ?? $this->get($tag);
			
			if (!empty($value)) {
				$tags[] = $this->buildMetaTag([
					'property' => "og:{$tag}",
					'content'  => $value,
				]);
			}
		}
		
		return implode('', $tags);
	}
	
	/**
	 * Generate Facebook App ID tag
	 */
	public function fbAppId(): string
	{
		$appId = $this->get('fb:app_id');
		
		if (empty($appId)) {
			return '';
		}
		
		return $this->buildMetaTag([
			'property' => 'fb:app_id',
			'content'  => $appId,
		]);
	}
	
	/**
	 * Generate Twitter Card meta tags
	 */
	public function twitterCard(): string
	{
		$tags = [];
		$processedTags = [];
		
		foreach (self::SUPPORTED_TWITTER_TAGS as $tag) {
			$value = $this->config['twitter'][$tag] ?? $this->get($tag);
			
			if (!empty($value) && !isset($processedTags[$tag])) {
				$tags[] = $this->buildMetaTag([
					'name'    => "twitter:{$tag}",
					'content' => $value,
				]);
				$processedTags[$tag] = true;
			}
		}
		
		// Handle image fallback
		if (!isset($processedTags['image:src'])) {
			$imageValue = $this->get('image');
			if (!empty($imageValue)) {
				$tags[] = $this->buildMetaTag([
					'name'    => 'twitter:image:src',
					'content' => $imageValue,
				]);
			}
		}
		
		// Handle domain fallback
		if (!isset($processedTags['domain'])) {
			$tags[] = $this->buildMetaTag([
				'name'    => 'twitter:domain',
				'content' => $this->request->getHttpHost(),
			]);
		}
		
		return implode('', $tags);
	}
	
	/**
	 * Build a meta tag from attributes
	 */
	private function buildMetaTag(array $attributes): string
	{
		return $this->buildHtmlTag('meta', $attributes);
	}
	
	/**
	 * Build a link tag from attributes
	 */
	private function buildLinkTag(array $attributes): string
	{
		return $this->buildHtmlTag('link', $attributes);
	}
	
	/**
	 * Build an HTML tag from attributes
	 */
	private function buildHtmlTag(string $tagName, array $attributes): string
	{
		$attributeStrings = [];
		
		foreach ($attributes as $name => $value) {
			$normalizedValue = $this->normalizeMetaTagValue($value);
			if ($normalizedValue !== null && $normalizedValue !== '') {
				$attributeStrings[] = sprintf('%s="%s"', $name, $normalizedValue);
			}
		}
		
		if (empty($attributeStrings)) {
			return '';
		}
		
		$attributeString = implode(' ', $attributeStrings);
		
		return "<{$tagName} {$attributeString}>\n    ";
	}
	
	/**
	 * Normalize and sanitize meta tag values
	 */
	private function normalizeMetaTagValue(?string $text): ?string
	{
		if (empty($text)) {
			return $text;
		}
		
		// Clean up the text (assuming this function exists in your codebase)
		if (function_exists('singleLineStringCleanerStrict')) {
			$text = singleLineStringCleanerStrict($text);
		} else {
			// Fallback cleanup
			$text = strip_tags($text);
			$text = preg_replace('/\s+/', ' ', trim($text));
			$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
		}
		
		return $text;
	}
	
	/**
	 * Cut text to specified length with proper handling
	 */
	private function cutText(?string $text, int|string $keyOrLimit): ?string
	{
		if (empty($text)) {
			return $text;
		}
		
		$limit = $this->determineTextLimit($keyOrLimit);
		
		if ($limit <= 0) {
			return $text;
		}
		
		// Use Laravel's Str helper if available, otherwise fallback
		if (function_exists('str')) {
			return str($text)->limit($limit)->toString();
		}
		
		return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
	}
	
	/**
	 * Determine the text limit from key or direct limit value
	 */
	private function determineTextLimit(int|string $keyOrLimit): int
	{
		return match (true) {
			is_int($keyOrLimit)    => $keyOrLimit,
			is_string($keyOrLimit) => $this->config[$keyOrLimit . '_limit'] ?? self::DEFAULT_LIMITS[$keyOrLimit] ?? 0,
			default                => 0,
		};
	}
	
	/**
	 * Generate a localized URL for the given locale
	 */
	private function generateLocalizedUrl(string $locale): string
	{
		$isDefaultLocale = ($locale === $this->defaultLocale);
		$subdomain = $isDefaultLocale ? '' : strtolower($locale) . '.';
		
		$uri = $this->request->getRequestUri();
		$scheme = $this->request->getScheme();
		$host = $this->extractBaseDomain();
		
		$url = str_replace(
			['[scheme]', '[locale]', '[host]', '[uri]'],
			[$scheme, $subdomain, $host, $uri],
			$this->config['locale_url']
		);
		
		// Use Laravel's url helper if available
		if (function_exists('url')) {
			$url = url($url);
		}
		
		// Use helper function if available, otherwise return as-is
		return function_exists('castToString') ? castToString($url) : (string)$url;
	}
	
	/**
	 * Extract the base domain from HTTP host
	 */
	private function extractBaseDomain(): string
	{
		$httpHost = $this->request->getHttpHost();
		$hostParts = explode('.', $httpHost);
		$partsCount = count($hostParts);
		
		if ($partsCount < 2) {
			return $httpHost;
		}
		
		// Return domain.tld (last two parts)
		return implode('.', array_slice($hostParts, -2));
	}
	
	/**
	 * Bulk set multiple meta values
	 */
	public function setMultiple(array $metas): self
	{
		foreach ($metas as $key => $value) {
			if (is_string($key)) {
				$this->set($key, $value);
			}
		}
		
		return $this;
	}
	
	/**
	 * Check if a meta key exists
	 */
	public function has(string $key): bool
	{
		return isset($this->metas[$key]);
	}
	
	/**
	 * Remove a meta value
	 */
	public function forget(string $key): self
	{
		unset($this->metas[$key]);
		
		return $this;
	}
	
	/**
	 * Get all meta values
	 */
	public function all(): array
	{
		return $this->metas;
	}
}
