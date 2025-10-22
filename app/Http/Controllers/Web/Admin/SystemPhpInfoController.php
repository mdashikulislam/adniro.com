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

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Panel\PanelController;

class SystemPhpInfoController extends PanelController
{
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Capture phpinfo output
		ob_start();
		phpinfo();
		$phpinfo = ob_get_clean();
		
		// Parse and clean up the HTML if needed
		$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
		
		// Apply Bootstrap classes automatically
		$phpinfo = $this->applyBootstrapStyling($phpinfo);
		
		return view('admin.system.phpinfo', compact('phpinfo'));
	}
	
	/**
	 * @return \Illuminate\Http\Response
	 */
	public function rawVersion()
	{
		// Capture phpinfo output
		ob_start();
		phpinfo();
		$phpinfo = ob_get_clean();
		
		// Return as raw HTML response
		return response($phpinfo, 200)->header('Content-Type', 'text/html');
	}
	
	// PRIVATE
	
	/**
	 * Apply Bootstrap styling to phpinfo HTML
	 *
	 * @param $html
	 * @return string
	 */
	private function applyBootstrapStyling($html)
	{
		// Replace tables with Bootstrap table classes
		$html = str_replace('<table', '<table class="table table-responsive table-striped table-hover table-sm mb-4"', $html);
		
		// Style table headers
		$html = preg_replace('/<th([^>]*)>/i', '<th class="table-dark text-white"$1>', $html);
		
		// Add Bootstrap classes to h1 tags
		$html = preg_replace('/<h1([^>]*)>/i', '<h1 class="display-6 text-primary mb-4 pb-2 border-bottom"$1>', $html);
		
		// Add Bootstrap classes to h2 tags
		$html = preg_replace('/<h2([^>]*)>/i', '<h2 class="h4 text-secondary mt-5 mb-3 fw-bold"$1>', $html);
		
		// Style images (PHP logo, etc.)
		$html = str_replace('<img', '<img class="img-fluid mx-auto d-block mb-3"', $html);
		
		// Add spacing to paragraphs
		$html = str_replace('<p>', '<p class="mb-2">', $html);
		
		// Style any existing divs
		$html = preg_replace('/<div class="center">/', '<div class="mb-4">', $html);
		
		// Add card styling to main sections
		$html = preg_replace(
			pattern: '/(<h2[^>]*>.*?<\/h2>)(.*?)(?=<h2|$)/s',
			replacement: '$1<div class="card mb-4"><div class="card-body">$2</div></div>',
			subject: $html
		);
		
		// Style any code blocks or pre tags
		$html = str_replace('<pre>', '<pre class="bg-light p-3 rounded border">', $html);
		
		// Style any existing font tags or spans with colors
		$html = preg_replace('/<font color="([^"]*)"([^>]*)>/i', '<span class="text-info fw-bold"$2>', $html);
		$html = str_replace('</font>', '</span>', $html);
		
		return trim($html);
	}
}
