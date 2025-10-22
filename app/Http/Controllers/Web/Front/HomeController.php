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

namespace App\Http\Controllers\Web\Front;

use App\Services\SectionService;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class HomeController extends FrontController
{
	protected SectionService $sectionService;
	
	/**
	 * @param \App\Services\SectionService $sectionService
	 */
	public function __construct(SectionService $sectionService)
	{
		parent::__construct();
		
		$this->sectionService = $sectionService;
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Get homepage sections
		$data = getServiceData($this->sectionService->getSections());
		
		$message = data_get($data, 'message');
		$sections = (array)data_get($data, 'result.data');
		
		// Customize the sections for Blade
		$sections = collect($sections)
			->map(function ($item) {
				$belongsTo = $item['belongs_to'] ?? '';
				$name = $item['name'] ?? '';
				
				$item['optionName'] = str($name)
					->lower()
					->camel()
					->append('Options')
					->toString();
				
				$item['view'] = str($name)
					->slug()
					->prepend('front.sections.' . $belongsTo . '.')
					->toString();
				
				return $item;
			})
			->toArray();
		
		// Share sections' options in views,
		// that requires to be accessible everywhere in the app's views (including the master view).
		foreach ($sections as $section) {
			$optionName = data_get($section, 'optionName');
			$options = (array)data_get($section, 'options');
			view()->share($optionName, $options);
		}
		
		$isFromHome = routeActionHas('HomeController');
		
		// Get SEO
		$searchFormOptions = data_get($sections, 'search_form.options') ?? [];
		$this->setSeo($searchFormOptions);
		
		return view('front.index', compact('sections', 'isFromHome'));
	}
	
	/**
	 * Set SEO information
	 *
	 * @param array $searchFormOptions
	 */
	private function setSeo(array $searchFormOptions = []): void
	{
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('home');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		try {
			$this->og->title($title)->description($description);
			
			$ogImageUrl = config('settings.social_share.og_image_url');
			$ogImageUrl = empty($ogImageUrl) ? config('country.background_image_url') : $ogImageUrl;
			$ogImageUrl = empty($ogImageUrl) ? data_get($searchFormOptions, 'background_image_url') : $ogImageUrl;
			$ogImageUrl = castToStringOrNull($ogImageUrl);
			if (!empty($ogImageUrl)) {
				if ($this->og->has('image')) {
					$this->og->forget('image')->forget('image:width')->forget('image:height');
				}
				$this->og->image($ogImageUrl, [
					'width'  => (int)config('settings.social_share.og_image_width', 1200),
					'height' => (int)config('settings.social_share.og_image_height', 630),
				]);
			}
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
	}
}
