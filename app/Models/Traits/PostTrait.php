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

namespace App\Models\Traits;

use App\Helpers\Common\Date;
use App\Helpers\Common\Date\TimeZoneManager;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Models\Post;
use Spatie\Feed\FeedItem;

trait PostTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function crudTitleColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = getPostUrl($this);
		$out .= '<br>';
		$out .= '<small>';
		$out .= $this->pictures->count() . ' ' . trans('admin.pictures');
		$out .= '</small>';
		
		if (!empty($this->archived_at)) {
			$out .= '<br>';
			$out .= '<span class="badge bg-secondary">';
			$out .= trans('admin.Archived');
			$out .= '</span>';
		}
		
		return $out;
	}
	
	public function crudPictureColumn(?Panel $xPanel = null, array $column = []): string
	{
		// Get listing URL
		$url = dmUrl($this->country_code ?? null, urlGen()->postPath($this));
		
		$defaultPictureUrl = thumbParam(config('larapen.media.picture'))->url();
		
		// Get the first picture
		$style = ' style="width:auto; max-height:90px;"';
		$pictureUrl = $this->picture->file_url_small ?? $defaultPictureUrl;
		$out = '<img src="' . $pictureUrl . '" data-bs-toggle="tooltip" title="' . $this->title . '"' . $style . ' class="img-rounded">';
		
		// Add a link to the listing
		return '<a href="' . $url . '" target="_blank">' . $out . '</a>';
	}
	
	public function crudUserNameColumn(?Panel $xPanel = null, array $column = [])
	{
		$contactName = $this->contact_name ?? '-';
		
		if (!empty($this->user)) {
			$url = urlGen()->adminUrl('users/' . $this->user->getKey() . '/edit');
			$tooltip = ' data-bs-toggle="tooltip" title="' . $this->user->name . '"';
			
			return '<a href="' . $url . '"' . $tooltip . '>' . $contactName . '</a>';
		} else {
			return $contactName;
		}
	}
	
	public function crudCityColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = $this->crudCountryColumn($xPanel);
		$out .= ' - ';
		if (!empty($this->city)) {
			$out .= '<a href="' . urlGen()->city($this->city) . '" target="_blank">' . $this->city->name . '</a>';
		} else {
			$out .= $this->city_id ?? 0;
		}
		
		return $out;
	}
	
	public function crudReviewedColumn(?Panel $xPanel = null, array $column = []): string
	{
		return ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'reviewed_at', ($this->reviewed_at ?? null));
	}
	
	public function crudFeaturedColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = '-';
		if (config('plugins.offlinepayment.installed')) {
			$opTool = '\extras\plugins\offlinepayment\app\Helpers\OpTools';
			if (class_exists($opTool)) {
				$out = $opTool::featuredCheckboxDisplay(
					$this->{$this->primaryKey},
					$this->getTable(),
					'featured',
					($this->featured ?? null)
				);
			}
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	public static function getFeedItems()
	{
		$perPage = (int)config('settings.pagination.per_page', 50);
		
		$countryCode = (config('plugins.domainmapping.installed'))
			? config('country.code')
			: request()->input('country');
		
		$cacheParams = [
			'action'      => 'get.listings',
			'reviewed'    => true,
			'unarchived'  => true,
			'country'     => $countryCode,
			'perPage'     => $perPage,
			'orderByDesc' => 'id',
		];
		
		return caching()->remember(Post::class, $cacheParams, function () use ($countryCode, $perPage) {
			$posts = Post::query()
				->reviewed()
				->unarchived()
				->when(!empty($countryCode), fn ($query) => $query->where('country_code', '=', $countryCode))
				->take($perPage)
				->orderByDesc('id');
			
			return $posts->get();
		});
	}
	
	public function toFeedItem(): FeedItem
	{
		$title = $this->title ?? 'Untitled';
		$title .= !empty($this->city) ? ' - ' . $this->city->name : '';
		$title .= !empty($this->country) ? ', ' . $this->country->name : '';
		$summary = multiLinesStringCleaner($this->description ?? 'Description');
		$link = urlGen()->post($this, true);
		$tz = TimeZoneManager::getContextualTimeZone();
		
		return FeedItem::create()
			->id($link)
			->title($title)
			->summary($summary)
			->category($this?->category?->name ?? '')
			->updated($this->updated_at ?? Date::format(now($tz), 'datetime'))
			->link($link)
			->authorName($this->contact_name ?? 'Unknown author');
	}
}
