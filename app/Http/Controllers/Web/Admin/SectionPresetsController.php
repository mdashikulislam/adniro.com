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

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\JsonUtils;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SectionPresetsController extends Controller
{
	private array $data = [];
	
	public function __construct()
	{
		parent::__construct();
		
		$disk = StorageDisk::getDisk();
		
		$presets = getCachedReferrerList('presets');
		$presets = collect($presets)
			->map(function ($preset) use ($disk) {
				$imagePath = $preset['image'] ?? null;
				$preset['image'] = !empty($imagePath) ? $disk->url($imagePath) : null;
				
				return $preset;
			})->toArray();
		
		$this->data['presets'] = $presets;
	}
	
	/**
	 * Show all presets
	 */
	public function showPresetList()
	{
		$this->data['title'] = 'Sections Presets (Homepage)';
		
		return view('admin.presets', $this->data);
	}
	
	/**
	 * Apply the selected preset
	 *
	 * @param int $index
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function applyPreset(int $index): JsonResponse
	{
		$preset = request()->input('preset');
		$preset = JsonUtils::jsonToArray($preset);
		request()->merge(['preset' => $preset]);
		
		// Retrieve input preset per entity
		$settingPreset = $preset['setting'] ?? [];
		$sectionPreset = $preset['section'] ?? '';
		
		// Retrieve the preset object (array)
		$presetObj = $this->data['presets'][$index] ?? null;
		$presetName = $presetObj['name'] ?? 'Unknown';
		
		$res = null;
		
		// Update Setting
		$settingKeys = array_keys($settingPreset);
		$settings = Setting::query()->whereIn('name', $settingKeys)->get();
		if ($settings->isNotEmpty()) {
			foreach ($settings as $setting) {
				// Values are updated during retrieving related to the request()->input('preset') data
				$fieldValue = $setting->field_values ?? [];
				$setting->field_values = $fieldValue;
				$res = $setting->save();
			}
		}
		
		// Update Section
		$sections = Section::query()->get();
		if ($sections->isNotEmpty()) {
			foreach ($sections as $section) {
				// Values are updated during retrieving related to the request()->input('preset') data
				$fieldValue = $section->field_values ?? [];
				$section->field_values = $fieldValue;
				
				if ($section->name == 'search_form') {
					$section->lft = 0;
					$section->rgt = 1;
					$section->active = 1;
				}
				
				$res = $section->save();
			}
		}
		
		if (is_bool($res)) {
			if ($res) {
				$message = trans('admin.preset_applied_successfully', ['preset' => $presetName]);
				$status = 200;
			} else {
				$message = trans('admin.preset_application_failed', ['preset' => $presetName]);
				$status = 400;
			}
		} else {
			$message = trans('admin.no_changes_no_preset_applied', ['preset' => $presetName]);
			$status = 200;
		}
		
		$resultData = [
			'success' => (bool)$res,
			'message' => $message,
			'status'  => $status,
		];
		
		return response()->json($resultData, $status);
	}
}
