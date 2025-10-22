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

use App\Helpers\Common\VideoEmbedder;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait FieldTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = $xPanel->getUrl($this->id . '/edit');
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function crudTypeColumn(?Panel $xPanel = null, array $column = [])
	{
		$types = self::fieldTypes();
		
		return (isset($types[$this->type])) ? $types[$this->type] : $this->type;
	}
	
	public function crudRequiredColumn(?Panel $xPanel = null, array $column = []): string
	{
		if (!isset($this->required)) return '';
		
		return checkboxDisplay($this->required);
	}
	
	public function optionsInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$out = '';
		
		if (isset($this->type) && self::fieldTypesHasOptions($this->type)) {
			$url = urlGen()->adminUrl("custom_fields/{$this->id}/options");
			
			$out .= '<a class="btn btn-xs btn-info" href="' . $url . '">';
			$out .= '<i class="fa-solid fa-gear"></i> ';
			$out .= mb_ucfirst(trans('admin.options'));
			$out .= '</a>';
		}
		
		return $out;
	}
	
	public function addToCategoryInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$url = urlGen()->adminUrl("custom_fields/{$this->id}/categories/create");
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '">';
		$out .= '<i class="fa-solid fa-plus"></i> ';
		$out .= trans('admin.Add to a Category');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	public static function fieldTypes(): array
	{
		// Get the videos embedding platforms
		$platforms = VideoEmbedder::getPlatforms();
		
		return [
			'text'              => 'Text',
			'textarea'          => 'Textarea',
			'checkbox'          => 'Checkbox',
			'checkbox_multiple' => 'Checkbox (Multiple)',
			'select'            => 'Select Box',
			'radio'             => 'Radio',
			'file'              => 'File',
			'url'               => 'URL',
			'video'             => 'Video URL ' . $platforms,
			'number'            => 'Number',
			'date'              => 'Date',
			'date_time'         => 'Date Time',
			'date_range'        => 'Date Range',
		];
	}
	
	public static function fieldTypesWithOptions(): array
	{
		return ['select', 'radio', 'checkbox_multiple'];
	}
	
	public static function fieldTypesHasOptions(?string $fieldType): bool
	{
		if (empty($fieldType)) return false;
		
		return in_array($fieldType, self::fieldTypesWithOptions());
	}
}
