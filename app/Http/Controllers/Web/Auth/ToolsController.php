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

namespace App\Http\Controllers\Web\Auth;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class ToolsController extends Controller
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
	 * Generate Skin & Custom CSS
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function skinCss()
	{
		$out = '';
		
		$hOut = '/* === CSS Version === */' . "\n";
		$hOut .= '/* === v' . config('version.app') . ' === */' . "\n";
		
		try {
			$out .= view('auth.layout.css.skin', ['disk' => $this->disk])->render();
			$out = preg_replace('|</?style[^>]*>|i', '', $out);
		} catch (Throwable $e) {
			$out .= '/* === CSS Error Found === */' . "\n";
		}
		
		$out = cssMinify($out);
		
		$out = $hOut . $out;
		
		return response($out, 200)->header('Content-Type', 'text/css');
	}
}
