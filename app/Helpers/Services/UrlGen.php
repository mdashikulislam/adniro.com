<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Helpers\Services;

use App\Helpers\Common\Arr;
use App\Helpers\Services\UrlGen\SearchTrait;
use App\Http\Controllers\Web\Auth\Traits\Custom\UrlGen\AccountUrlGen;
use App\Http\Controllers\Web\Auth\Traits\Custom\UrlGen\AuthUrlGen;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class UrlGen
{
	use AuthUrlGen;
	use AccountUrlGen;
	use SearchTrait;
	
	// ADMIN PANEL
	
	public function adminUri(?string $path = ''): string
	{
		$basePath = $this->getAdminBasePath();
		
		$path = str_replace(url($basePath), '', $path);
		$path = ltrim($path, '/');
		
		if (!empty($path)) {
			$path = $basePath . '/' . $path;
		} else {
			$path = $basePath;
		}
		
		return castToString($path, '/');
	}
	
	public function adminUrl(?string $path = ''): string
	{
		return url($this->adminUri($path));
	}
	
	public function getAdminBasePath(): string
	{
		$basePath = 'admin';
		$basePath = config('larapen.core.basePath.admin', $basePath);
		
		return castToString($basePath, '/');
	}
	
	// FRONT
	
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public function postPath($entry, bool $encoded = false): string
	{
		$entry = is_array($entry) ? Arr::toObject($entry) : $entry;
		
		if (isset($entry->id) && isset($entry->title)) {
			$preview = !isVerifiedPost($entry) ? '?preview=1' : '';
			
			$slug = ($encoded) ? rawurlencode($entry->slug) : $entry->slug;
			
			$path = str_replace(['{slug}', '{hashableId}', '{id}'], [$slug, hashId($entry->id), $entry->id], config('routes.post'));
			$path = $path . $preview;
		} else {
			$path = '/';
		}
		
		return castToString($path);
	}
	
	/**
	 * @param $id
	 * @param string $slug
	 * @return string
	 */
	public function postPathBasic($id, string $slug = 'listing-slug'): string
	{
		$path = str_replace(['{slug}', '{hashableId}', '{id}'], [$slug, $id, $id], config('routes.post'));
		
		return castToString($path);
	}
	
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public function postUri($entry, bool $encoded = false): string
	{
		return $this->postPath($entry, $encoded);
	}
	
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public function post($entry, bool $encoded = false): string
	{
		$entry = is_array($entry) ? Arr::toObject($entry) : $entry;
		
		if (config('plugins.domainmapping.installed')) {
			$url = dmUrl($entry->country_code, $this->postUri($entry, $encoded));
		} else {
			$url = url($this->postPath($entry, $encoded));
		}
		
		return urlBuilder($url)->toString();
	}
	
	/**
	 * @param string $id
	 * @param string $slug
	 * @return string
	 */
	public function postPathPattern(string $id, string $slug): string
	{
		$search = ['{slug}', '{hashableId}', '{id}'];
		$replace = [$slug, $id, $id];
		$subject = config('routes.post', '#entrySlug/#entryId');
		$nextUriPath = str_replace($search, $replace, $subject);
		
		return castToString($nextUriPath, '/');
	}
	
	/**
	 * @param string $id
	 * @param string $slug
	 * @return string
	 */
	public function postPattern(string $id, string $slug): string
	{
		return url($this->postPathPattern($id, $slug));
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function reportPost($entry): string
	{
		$entityId = is_numeric($entry) ? $entry : data_get($entry, 'id');
		
		if (!empty($entityId)) {
			$url = url('posts/' . hashId($entityId) . '/report');
		} else {
			$url = url('/');
		}
		
		return urlBuilder($url)->toString();
	}
	
	/**
	 * @return string
	 */
	public function addPost(): string
	{
		$url = isSingleStepFormEnabled()
			? url('create')
			: url('posts/create');
		
		return urlBuilder($url)
			->setParameters(request()->only(['packageId']))
			->toString();
	}
	
	/**
	 * @return string
	 */
	public function addPostPhotos(): string
	{
		$url = isSingleStepFormEnabled()
			? url('create')
			: url('posts/create/photos');
		
		return urlBuilder($url)
			->setParameters(request()->only(['packageId']))
			->toString();
	}
	
	/**
	 * @return string
	 */
	public function addPostPayment(): string
	{
		$url = isSingleStepFormEnabled()
			? url('create')
			: url('posts/create/payment');
		
		return urlBuilder($url)
			->setParameters(request()->only(['packageId']))
			->toString();
	}
	
	/**
	 * @return string
	 */
	public function addPostPaymentSuccess(): string
	{
		return isSingleStepFormEnabled()
			? url('create/payment/success')
			: url('posts/create/payment/success');
	}
	
	/**
	 * @return string
	 */
	public function addPostPaymentCancel(): string
	{
		return isSingleStepFormEnabled()
			? url('create/payment/cancel')
			: url('posts/create/payment/cancel');
	}
	
	/**
	 * @return string
	 */
	public function addPostFinished(): string
	{
		return isSingleStepFormEnabled()
			? url('create/finish')
			: url('posts/create/finish');
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function editPost($entry): string
	{
		$entityId = isStringableStrict($entry) ? $entry : data_get($entry, 'id');
		
		if (!empty($entityId)) {
			$url = isSingleStepFormEnabled()
				? url('edit/' . $entityId)
				: url('posts/' . $entityId . '/details');
		} else {
			$url = '/';
		}
		
		return urlBuilder($url)
			->setParameters(request()->only(['packageId']))
			->toString();
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function editPostPhotos($entry): string
	{
		$entityId = isStringableStrict($entry) ? $entry : data_get($entry, 'id');
		
		if (!empty($entityId)) {
			$url = isSingleStepFormEnabled()
				? url('edit/' . $entityId)
				: url('posts/' . $entityId . '/photos');
		} else {
			$url = '/';
		}
		
		return urlBuilder($url)->toString();
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function editPostPayment($entry): string
	{
		$entityId = isStringableStrict($entry) ? $entry : data_get($entry, 'id');
		
		if (!empty($entityId)) {
			$url = isSingleStepFormEnabled()
				? url('edit/' . $entityId)
				: url('posts/' . $entityId . '/payment');
		} else {
			$url = '/';
		}
		
		return urlBuilder($url)->toString();
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function editPostPaymentSuccess($entry): string
	{
		$entityId = isStringableStrict($entry) ? $entry : data_get($entry, 'id');
		
		return isSingleStepFormEnabled()
			? url('edit/' . $entityId . '/payment/success')
			: url('posts/' . $entityId . '/payment/success');
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function editPostPaymentCancel($entry): string
	{
		$entityId = isStringableStrict($entry) ? $entry : data_get($entry, 'id');
		
		return isSingleStepFormEnabled()
			? url('edit/' . $entityId . '/payment/cancel')
			: url('posts/' . $entityId . '/payment/cancel');
	}
	
	/**
	 * @param string|null $countryCode
	 * @return string
	 */
	public function companies(string $countryCode = null): string
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (isMultiCountriesUrlsEnabled()) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		$path = str_replace(['{countryCode}/'], [''], config('routes.companies'));
		$url = url($countryCodePath . $path);
		
		return urlBuilder($url)->toString();
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public function page($entry): string
	{
		$entry = is_array($entry) ? Arr::toObject($entry) : $entry;
		
		if (isset($entry->slug)) {
			$path = str_replace(['{slug}'], [$entry->slug], config('routes.pageBySlug'));
			$url = url($path);
		} else {
			$url = '/';
		}
		
		return urlBuilder($url)->toString();
	}
	
	/**
	 * @param string|null $countryCode
	 * @return string
	 */
	public function sitemap(string $countryCode = null): string
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (isMultiCountriesUrlsEnabled()) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		$path = str_replace(['{countryCode}/'], [''], config('routes.sitemap'));
		$url = url($countryCodePath . $path);
		
		return urlBuilder($url)->toString();
	}
	
	public function countries(): string
	{
		$url = url(config('routes.countries'));
		
		if (doesCountriesPageCanBeLinkedToTheHomepage()) {
			$url = str(config('app.url'))->finish('/')->toString();
			
			$crawler = new CrawlerDetect();
			if (!$crawler->isCrawler()) {
				$url = $url . 'locale/' . config('app.locale');
			}
		}
		
		return urlBuilder($url)->toString();
	}
	
	public function contact(): string
	{
		return urlBuilder(config('routes.contact'))->toString();
	}
	
	public function pricing(): string
	{
		return urlBuilder(config('routes.pricing'))->toString();
	}
}
