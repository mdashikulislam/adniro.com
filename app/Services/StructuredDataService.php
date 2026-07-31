<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Builds schema.org JSON-LD structures for the front pages.
 * Rendered by resources/views/front/common/json-ld.blade.php (included in the master layout).
 *
 * Rules:
 * - Any field without real data is omitted.
 * - Never output aggregateRating.
 */
class StructuredDataService
{
	// Leaf category slug => schema.org Vehicle subtype
	protected const VEHICLE_TYPES = [
		'cars'                      => 'Car',
		'motorcycles-and-scooters'  => 'Motorcycle',
	];

	protected const REAL_ESTATE_ROOT_SLUG = 'real-estate';

	// IDs from the "fields" table (stable custom fields)
	protected const FIELD_CAR_BRAND = 1;
	protected const FIELD_CAR_MODEL = 2;
	protected const FIELD_REG_YEAR = 3;
	protected const FIELD_FUEL_TYPE = 5;
	protected const FIELD_TRANSMISSION = 7;
	protected const FIELD_CONDITION = 8;
	protected const FIELD_COMPANY = 19;
	protected const FIELD_WORK_TYPE = 20;

	protected const EMPLOYMENT_TYPES = [
		'full-time'  => 'FULL_TIME',
		'part-time'  => 'PART_TIME',
		'temporary'  => 'TEMPORARY',
		'internship' => 'INTERN',
		'contract'   => 'CONTRACTOR',
		'freelance'  => 'CONTRACTOR',
		'volunteer'  => 'VOLUNTEER',
	];

	/**
	 * Schema for a listing details page:
	 * JobPosting for job-offer categories, otherwise Product+Offer
	 * (with Car/Motorcycle/RealEstateListing upgrades per category).
	 *
	 * @param array $post
	 * @param array $pictures
	 * @param array $customFields
	 * @return array|null
	 */
	public function forListing(array $post, array $pictures = [], array $customFields = []): ?array
	{
		if (empty($post['title'])) {
			return null;
		}

		if ($this->isJobOffer($post)) {
			return $this->jobPosting($post, $customFields);
		}

		return $this->product($post, $pictures, $customFields);
	}

	/**
	 * BreadcrumbList from a list of ['name' => ..., 'url' => ...(optional)] items
	 *
	 * Rules (Google breadcrumb guidance):
	 * - The last ListItem (current page) gets "name" only, never "item".
	 * - "item" URLs are absolute and canonical (query strings stripped).
	 * - Crumbs pointing to search URLs (noindexed, robots-blocked) are dropped.
	 *
	 * @param array $items
	 * @return array|null
	 */
	public function breadcrumbs(array $items): ?array
	{
		$cleanItems = [];
		$lastIndex = count($items) - 1;
		foreach (array_values($items) as $index => $item) {
			$name = html_entity_decode(strip_tags((string)data_get($item, 'name')));
			$name = normalizeWhitespace($name);
			if (trim($name) === '') {
				continue;
			}

			$url = $this->canonicalUrl(data_get($item, 'url'));
			$isCurrentPage = ($index >= $lastIndex);

			// Intermediate crumbs must link an indexable page; search URLs never are
			if (!$isCurrentPage && (empty($url) || $this->isSearchUrl($url))) {
				continue;
			}

			$cleanItems[] = ['name' => $name, 'url' => $isCurrentPage ? null : $url];
		}

		if (count($cleanItems) < 2) {
			return null;
		}

		$listItems = [];
		foreach (array_values($cleanItems) as $index => $item) {
			$listItem = [
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $item['name'],
			];
			if (!empty($item['url'])) {
				$listItem['item'] = $item['url'];
			}
			$listItems[] = $listItem;
		}

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $listItems,
		];
	}

	/**
	 * The country's canonical crawlable homepage URL
	 * (e.g. https://www.adniro.com/in), the site root otherwise.
	 *
	 * @return string
	 */
	public function countryHomeUrl(): string
	{
		$countryCode = strtolower((string)config('country.icode'));
		if (isMultiCountriesUrlsEnabled() && !empty($countryCode)) {
			return url('/' . $countryCode);
		}

		return url('/');
	}

	protected function canonicalUrl($url): ?string
	{
		if (empty($url) || !is_string($url)) {
			return null;
		}

		// Absolute URL without query string or fragment
		$url = strtok($url, '?');
		$url = strtok($url, '#');

		return str_starts_with($url, 'http') ? rtrim($url, '/') : null;
	}

	protected function isSearchUrl(string $url): bool
	{
		try {
			$searchPath = parse_url(urlGen()->searchWithoutQuery(), PHP_URL_PATH);
			$urlPath = parse_url($url, PHP_URL_PATH);

			return !empty($searchPath) && !empty($urlPath) && str_starts_with($urlPath, rtrim($searchPath, '/'));
		} catch (Throwable $e) {
			return false;
		}
	}

	/**
	 * Organization schema (homepage only)
	 *
	 * @return array|null
	 */
	public function organization(): ?array
	{
		$name = config('settings.app.name') ?: config('app.name');
		if (empty($name)) {
			return null;
		}

		return $this->filter([
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => $name,
			'url'      => url('/'),
			'logo'     => config('settings.app.logo_url'),
		]);
	}

	/**
	 * WebSite schema with SearchAction (homepage only)
	 *
	 * @return array|null
	 */
	public function website(): ?array
	{
		$name = config('settings.app.name') ?: config('app.name');
		if (empty($name)) {
			return null;
		}

		$searchUrl = null;
		try {
			$searchUrl = urlGen()->search([]);
			$searchUrl = urlBuilder($searchUrl)->removeParameters(['country'])->toString();
		} catch (Throwable $e) {
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'name'     => $name,
			'url'      => url('/'),
		];

		if (!empty($searchUrl)) {
			$separator = str_contains($searchUrl, '?') ? '&' : '?';
			$schema['potentialAction'] = [
				'@type'       => 'SearchAction',
				'target'      => [
					'@type'       => 'EntryPoint',
					'urlTemplate' => $searchUrl . $separator . 'q={search_term_string}',
				],
				'query-input' => 'required name=search_term_string',
			];
		}

		return $schema;
	}

	// Listing schema builders

	protected function product(array $post, array $pictures, array $customFields): array
	{
		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => $this->productType($post),
			'name'        => data_get($post, 'title'),
			'description' => $this->plainText(data_get($post, 'description')),
			'url'         => data_get($post, 'url'),
			'image'       => $this->imageUrls($pictures),
			'sku'         => data_get($post, 'reference') ?: (string)data_get($post, 'id'),
		];

		// Vehicle details (only real custom field values)
		if ($this->vehicleType($post) !== null) {
			$brand = $this->fieldValue($customFields, self::FIELD_CAR_BRAND);
			$schema['brand'] = !empty($brand) ? ['@type' => 'Brand', 'name' => $brand] : null;
			$schema['model'] = $this->fieldValue($customFields, self::FIELD_CAR_MODEL);
			$schema['vehicleModelDate'] = $this->fieldValue($customFields, self::FIELD_REG_YEAR);
			$schema['fuelType'] = $this->fieldValue($customFields, self::FIELD_FUEL_TYPE);
			$schema['vehicleTransmission'] = $this->fieldValue($customFields, self::FIELD_TRANSMISSION);
		}

		// Real estate listings carry a posting date
		if ($this->isRealEstate($post)) {
			$schema['datePosted'] = $this->isoDate(data_get($post, 'created_at'));
		}

		$schema['offers'] = $this->offer($post, $customFields);

		return $this->filter($schema);
	}

	protected function offer(array $post, array $customFields): ?array
	{
		$price = data_get($post, 'price');
		if ($price === null || $price === '') {
			return null;
		}

		$isArchived = !empty(data_get($post, 'archived_at'));

		return $this->filter([
			'@type'         => 'Offer',
			'price'         => (float)$price,
			'priceCurrency' => data_get($post, 'currency_code') ?: config('currency.code'),
			'availability'  => $isArchived ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
			'itemCondition' => $this->itemCondition($customFields),
			'url'           => data_get($post, 'url'),
			'seller'        => $this->seller($post),
		]);
	}

	protected function jobPosting(array $post, array $customFields): array
	{
		$company = $this->fieldValue($customFields, self::FIELD_COMPANY);
		$orgName = !empty($company) ? $company : $this->sellerName($post);

		$schema = [
			'@context'           => 'https://schema.org',
			'@type'              => 'JobPosting',
			'title'              => data_get($post, 'title'),
			'description'        => data_get($post, 'description'), // HTML allowed by Google
			'datePosted'         => $this->isoDate(data_get($post, 'created_at')),
			'validThrough'       => $this->validThrough($post),
			'employmentType'     => $this->employmentType($customFields),
			'hiringOrganization' => !empty($orgName) ? ['@type' => 'Organization', 'name' => $orgName] : null,
			'jobLocation'        => $this->jobLocation($post),
			'identifier'         => [
				'@type' => 'PropertyValue',
				'name'  => config('settings.app.name') ?: config('app.name'),
				'value' => (string)data_get($post, 'id'),
			],
		];

		return $this->filter($schema);
	}

	// Category helpers

	protected function isJobOffer(array $post): bool
	{
		return (data_get($post, 'category.type') === 'job-offer');
	}

	protected function isRealEstate(array $post): bool
	{
		return ($this->rootCategorySlug($post) === self::REAL_ESTATE_ROOT_SLUG);
	}

	protected function productType(array $post): string|array
	{
		$vehicleType = $this->vehicleType($post);
		if ($vehicleType !== null) {
			// Dual-typed: Google parses Product snippets from the Product type
			return ['Product', $vehicleType];
		}

		if ($this->isRealEstate($post)) {
			// Dual-typed: keeps Product/Offer properties valid while flagging real estate
			return ['Product', 'RealEstateListing'];
		}

		return 'Product';
	}

	protected function vehicleType(array $post): ?string
	{
		$catSlug = strtolower((string)data_get($post, 'category.slug'));

		return self::VEHICLE_TYPES[$catSlug] ?? null;
	}

	protected function rootCategorySlug(array $post): ?string
	{
		$catId = (int)data_get($post, 'category.id');
		if (empty($catId)) {
			return null;
		}

		$catsById = Cache::remember('schema.categories.byId', 3600, function () {
			return Category::query()->get(['id', 'parent_id', 'slug'])->keyBy('id');
		});

		$cat = $catsById->get($catId);
		$depthGuard = 0;
		while (!empty($cat) && !empty($cat->parent_id) && $depthGuard++ < 10) {
			$cat = $catsById->get($cat->parent_id);
		}

		return !empty($cat) ? strtolower((string)$cat->slug) : null;
	}

	// Field helpers

	protected function fieldValue(array $customFields, int $fieldId): ?string
	{
		foreach ($customFields as $field) {
			if ((int)data_get($field, 'id') === $fieldId) {
				$value = data_get($field, 'value');

				return (is_string($value) && trim($value) !== '') ? trim($value) : null;
			}
		}

		return null;
	}

	protected function itemCondition(array $customFields): ?string
	{
		$condition = strtolower((string)$this->fieldValue($customFields, self::FIELD_CONDITION));

		return match ($condition) {
			'new'  => 'https://schema.org/NewCondition',
			'used' => 'https://schema.org/UsedCondition',
			default => null,
		};
	}

	protected function employmentType(array $customFields): ?string
	{
		$workType = strtolower((string)$this->fieldValue($customFields, self::FIELD_WORK_TYPE));

		return self::EMPLOYMENT_TYPES[$workType] ?? null;
	}

	protected function validThrough(array $post): ?string
	{
		$archivedAt = data_get($post, 'archived_at');
		if (!empty($archivedAt)) {
			return $this->isoDate($archivedAt);
		}

		// Live listings expire at created_at + the configured expiration delay
		if (data_get($post, 'is_permanent')) {
			return null;
		}
		$expirationDays = (int)config('settings.cron.activated_posts_expiration', 0);
		$createdAt = data_get($post, 'created_at');
		if ($expirationDays <= 0 || empty($createdAt)) {
			return null;
		}

		try {
			return Carbon::parse($createdAt)->addDays($expirationDays)->toIso8601String();
		} catch (Throwable $e) {
			return null;
		}
	}

	protected function jobLocation(array $post): ?array
	{
		$cityName = data_get($post, 'city.name');
		$countryCode = data_get($post, 'country_code');
		if (empty($cityName) && empty($countryCode)) {
			return null;
		}

		// "US.FL" => "FL"
		$region = null;
		$subAdminCode = (string)data_get($post, 'city.subadmin1_code');
		if (str_contains($subAdminCode, '.')) {
			$region = substr($subAdminCode, strpos($subAdminCode, '.') + 1);
		}

		return $this->filter([
			'@type'   => 'Place',
			'address' => $this->filter([
				'@type'           => 'PostalAddress',
				'streetAddress'   => data_get($post, 'address'),
				'addressLocality' => $cityName,
				'addressRegion'   => $region,
				'addressCountry'  => $countryCode,
			]),
		]);
	}

	protected function seller(array $post): ?array
	{
		$name = $this->sellerName($post);

		return !empty($name) ? ['@type' => 'Person', 'name' => $name] : null;
	}

	protected function sellerName(array $post): ?string
	{
		$name = data_get($post, 'contact_name') ?: data_get($post, 'user.name');

		return !empty($name) ? (string)$name : null;
	}

	protected function imageUrls(array $pictures): array
	{
		$urls = [];
		foreach ($pictures as $picture) {
			$url = data_get($picture, 'url.full') ?: data_get($picture, 'url.large');
			if (!empty($url)) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	protected function plainText($html): ?string
	{
		if (empty($html)) {
			return null;
		}
		$text = normalizeWhitespace(html_entity_decode(strip_tags((string)$html)));

		return str($text)->limit(5000)->toString();
	}

	protected function isoDate($date): ?string
	{
		if (empty($date)) {
			return null;
		}

		try {
			return Carbon::parse($date)->toIso8601String();
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * Remove keys with no real data (null, empty string, empty array)
	 */
	protected function filter(array $schema): array
	{
		return array_filter($schema, function ($value) {
			return !($value === null || $value === '' || $value === []);
		});
	}
}
