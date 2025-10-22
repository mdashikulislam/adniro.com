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

namespace App\Http\Controllers\Web\Front\Page;

use App\Http\Controllers\Web\Front\FrontController;
use App\Services\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class PageController extends FrontController
{
	protected PageService $pageService;
	
	public function __construct(PageService $pageService)
	{
		parent::__construct();
		
		$this->pageService = $pageService;
	}
	
	/**
	 * @param string $slug
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function show(string $slug): View|RedirectResponse
	{
		// Get Packages
		$data = getServiceData($this->pageService->getEntry($slug));
		
		$message = data_get($data, 'message');
		$page = data_get($data, 'result');
		$breadcrumbs = data_get($page, 'breadcrumbs');
		
		// Check if an external link is available
		$externalLink = data_get($page, 'external_link');
		if (!empty($externalLink)) {
			return redirect()->away($externalLink, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('staticPage');
		$title = str_replace('{page.title}', data_get($page, 'seo_title'), $title);
		$title = str_replace('{app.name}', config('app.name'), $title);
		$title = str_replace('{country.name}', config('country.name'), $title);
		
		$description = str_replace('{page.description}', data_get($page, 'seo_description'), $description);
		$description = str_replace('{app.name}', config('app.name'), $description);
		$description = str_replace('{country.name}', config('country.name'), $description);
		
		$keywords = str_replace('{page.keywords}', data_get($page, 'seo_keywords'), $keywords);
		$keywords = str_replace('{app.name}', config('app.name'), $keywords);
		$keywords = str_replace('{country.name}', config('country.name'), $keywords);
		
		if (empty($title)) {
			$title = data_get($page, 'title') . ' - ' . config('app.name');
		}
		if (empty($description)) {
			$description = str(normalizeWhitespace(strip_tags(data_get($page, 'content'))))->limit(200);
		}
		
		$title = removeUnmatchedPatterns($title);
		$description = removeUnmatchedPatterns($description);
		$keywords = removeUnmatchedPatterns($keywords);
		
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		try {
			$this->og->title($title)->description($description);
			if (!empty(data_get($page, 'image_url'))) {
				if ($this->og->has('image')) {
					$this->og->forget('image')->forget('image:width')->forget('image:height');
				}
				$this->og->image(data_get($page, 'image_url'), [
					'width'  => (int)config('settings.social_share.og_image_width', 1200),
					'height' => (int)config('settings.social_share.og_image_height', 630),
				]);
			}
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
		
		return view('front.pages.cms', compact('page', 'breadcrumbs'));
	}
}
