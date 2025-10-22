<?php

use App\Exceptions\Custom\CustomException;
use App\Helpers\Common\JsonUtils;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ===| FILES |===
try {
	
	// Files
	File::delete(app_path('Http/Controllers/Web/Admin/SystemController.php'));
	File::delete(resource_path('views/admin/js/setting/style.blade.php'));
	File::delete(resource_path('views/admin/system.blade.php'));
	File::delete(resource_path('views/front/sections/spacer.blade.php'));
	
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	// settings
	$settingTable = (new Setting())->getTable();
	if (Schema::hasColumn($settingTable, 'name')) {
		$styleSetting = Setting::where('name', 'style')->first();
		$styleSettingValues = $styleSetting->field_values ?? [];
		
		// settings (header)
		$headerSetting = Setting::where('name', 'header')->first();
		if (empty($headerSetting)) {
			$styleToHeaderMap = [
				'header_full_width'                => 'full_width',
				'header_height'                    => 'height',
				'dark_header'                      => 'dark',
				'header_shadow'                    => 'shadow',
				'header_background_class'          => 'background_class',
				'header_background_color'          => 'background_color',
				'header_border_bottom_width'       => 'border_bottom_width',
				'header_border_bottom_color'       => 'border_bottom_color',
				'header_link_color'                => 'link_color',
				'header_link_hover_color'          => 'link_hover_color',
				'header_animation'                 => 'animation',
				'header_fixed_top'                 => 'fixed_top',
				'header_height_offset'             => 'fixed_height_offset',
				'fixed_dark_header'                => 'fixed_dark',
				'fixed_header_shadow'              => 'fixed_shadow',
				'fixed_header_background_class'    => 'fixed_background_class',
				'fixed_header_background_color'    => 'fixed_background_color',
				'fixed_header_border_bottom_width' => 'fixed_border_bottom_width',
				'fixed_header_border_bottom_color' => 'fixed_border_bottom_color',
				'fixed_header_link_color'          => 'fixed_link_color',
				'fixed_header_link_hover_color'    => 'fixed_link_hover_color',
				'logo_width'                       => 'logo_width',
				'logo_height'                      => 'logo_height',
				'logo_aspect_ratio'                => 'logo_aspect_ratio',
			];
			
			$headerSettingValues = [];
			foreach ($styleToHeaderMap as $styleKey => $headerKey) {
				if (array_key_exists($styleKey, $styleSettingValues)) {
					$headerSettingValues[$headerKey] = $styleSettingValues[$styleKey];
				}
			}
			
			$data = [
				'name'         => 'header',
				'label'        => 'Header',
				'fields'       => null,
				'field_values' => JsonUtils::ensureJson($headerSettingValues),
				'description'  => 'Pages Header',
				'parent_id'    => null,
				'lft'          => 3,
				'rgt'          => 4,
				'depth'        => 0,
				'active'       => 1,
			];
			DB::table($settingTable)->insert($data);
		}
		
		// settings (footer)
		$styleToFooterMap = [
			'dark_footer'                     => 'dark',
			'high_spacing_footer'             => 'high_spacing',
			'footer_full_width'               => 'full_width',
			'footer_background_color'         => 'background_color',
			'footer_border_top_width'         => 'border_top_width',
			'footer_border_top_color'         => 'border_top_color',
			'footer_text_color'               => 'text_color',
			'footer_title_color'              => 'title_color',
			'footer_link_color'               => 'link_color',
			'footer_link_hover_color'         => 'link_hover_color',
			'footer_inside_line_border_color' => 'inside_line_border_color',
		];
		
		$footerSetting = Setting::where('name', 'footer')->first();
		
		$footerSettingValues = $footerSetting->field_values ?? [];
		foreach ($styleToFooterMap as $styleKey => $footerKey) {
			if (array_key_exists($styleKey, $styleSettingValues)) {
				$footerSettingValues[$footerKey] = $styleSettingValues[$styleKey];
			}
		}
		
		DB::table($settingTable)
			->where('name', '=', 'footer')
			->update([
				'field_values' => JsonUtils::ensureJson($footerSettingValues),
				'lft'          => 5,
				'rgt'          => 6,
				'depth'        => 0,
			]);
		
		// settings (style)
		DB::table($settingTable)
			->where('name', '=', 'style')
			->update([
				'lft'          => 7,
				'rgt'          => 8,
				'depth'        => 0,
			]);
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
