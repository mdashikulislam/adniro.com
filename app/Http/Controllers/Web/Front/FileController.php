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

use App\Helpers\Common\Files\Response\FileContentResponseCreator;
use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class FileController extends Controller
{
	protected Filesystem $disk;
	private static ?string $diskName = null;
	
	public function __construct()
	{
		$tmpDiskName = request()->input('disk');
		if (!empty($tmpDiskName) && is_string($tmpDiskName)) {
			$allowedNames = ['private', 'public'];
			if (config('filesystems.disks.' . $tmpDiskName) && in_array($tmpDiskName, $allowedNames)) {
				self::$diskName = $tmpDiskName;
			}
		}
		
		$this->disk = StorageDisk::getDisk(self::$diskName);
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = [];
		
		if (self::$diskName == 'private') {
			$array[] = new Middleware('auth', only: ['show']);
		}
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * Get & watch media file (image, audio & video) content
	 *
	 * @param \App\Helpers\Common\Files\Response\FileContentResponseCreator $response
	 * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\StreamedResponse|null
	 */
	public function watchMediaContent(FileContentResponseCreator $response)
	{
		$filePath = request()->input('path');
		$filePath = preg_replace('|\?.*|ui', '', $filePath);
		
		try {
			$out = $response::create($this->disk, $filePath);
		} catch (Throwable $e) {
			abort(400, $e->getMessage());
		}
		
		if (ob_get_length()) {
			ob_end_clean(); // HERE IS THE MAGIC
		}
		
		return $out;
	}
	
	/**
	 * Translation of the bootstrap-fileinput plugin
	 *
	 * @param string $code
	 * @return \Illuminate\Http\Response|void
	 */
	public function bootstrapFileinputLocales(string $code = 'en')
	{
		$fileInputArray = trans('fileinput', [], $code);
		if (is_array($fileInputArray) && !empty($fileInputArray)) {
			if (config('settings.optimization.minify_html_activation') == 1) {
				$fileInputJson = json_encode($fileInputArray, JSON_UNESCAPED_UNICODE);
			} else {
				$fileInputJson = json_encode($fileInputArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			}
			
			if (!empty($fileInputJson)) {
				// $fileInputJson = str_replace('<\/', '</', $fileInputJson);
				
				$out = "(function (factory) {" . "\n";
				$out .= "   'use strict';" . "\n";
				$out .= "if (typeof define === 'function' && define.amd) {" . "\n";
				$out .= "   define(['jquery'], factory);" . "\n";
				$out .= "} else if (typeof module === 'object' && typeof module.exports === 'object') {" . "\n";
				$out .= "   factory(require('jquery'));" . "\n";
				$out .= "} else {" . "\n";
				$out .= "   factory(window.jQuery);" . "\n";
				$out .= "}" . "\n";
				$out .= '}(function ($) {' . "\n";
				$out .= '"use strict";' . "\n\n";
				
				$out .= "$.fn.fileinputLocales['$code'] = ";
				$out .= $fileInputJson . ';' . "\n";
				$out .= '}));' . "\n";
				
				return response($out, 200)->header('Content-Type', 'application/javascript');
			}
		}
		
		$locale = getLangTag(config('app.locale'));
		$filePath = public_path('assets/plugins/bootstrap-fileinput/js/locales/' . $locale . '.js');
		if (file_exists($filePath)) {
			$out = file_get_contents($filePath);
			
			return response($out, 200)->header('Content-Type', 'application/javascript');
		}
		
		abort(404, 'File not found!');
	}
	
	/**
	 * Generate Skin & Custom CSS
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function cssStyle()
	{
		$out = '';
		
		$hOut = '/* === CSS Version === */' . "\n";
		$hOut .= '/* === v' . config('version.app') . ' === */' . "\n";
		
		try {
			$out .= view('front.common.css.style', ['disk' => $this->disk])->render();
			$out = preg_replace('|</?style[^>]*>|i', '', $out);
		} catch (Throwable $e) {
			$out .= '/* === CSS Error Found === */' . "\n";
		}
		
		$isMinifyDisabled = request()->filled('minifyDisabled');
		$isDebugEnabled = request()->filled('debug');
		
		if (!$isMinifyDisabled) {
			$out = cssMinify($out);
		}
		
		$out = $hOut . $out;
		
		if ($isDebugEnabled) {
			dd($out);
		}
		
		return response($out, 200)->header('Content-Type', 'text/css');
	}
}
