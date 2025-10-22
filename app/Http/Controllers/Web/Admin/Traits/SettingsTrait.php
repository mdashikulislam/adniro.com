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

namespace App\Http\Controllers\Web\Admin\Traits;

use App\Helpers\Common\JsonUtils;
use Illuminate\Http\RedirectResponse;
use Throwable;

trait SettingsTrait
{
	/**
	 * @param $id
	 * @return \Illuminate\View\View
	 */
	public function edit($id)
	{
		$this->xPanel->hasAccessOrFail('update');
		
		// Get the right resource identifier
		$id = $this->xPanel->getResourceIdentifier($id);
		
		$entry = $this->xPanel->getEntry($id);
		
		// Add the 'fields' column's fake fields
		$fields = $entry->fields ?? [];
		$fields = JsonUtils::jsonToArray($fields);
		$this->addField($fields);
		
		// ...
		$this->data['xPanel'] = $this->xPanel;
		$this->data['entry'] = $entry;
		$this->data['id'] = $id;
		$this->data['saveAction'] = $this->getSaveAction();
		$this->data['fields'] = $this->xPanel->getUpdateFields($id);
		$this->data['title'] = trans('admin.edit') . ' ' . $this->xPanel->entityName;
		
		return view('admin.panel.edit', $this->data);
	}
	
	/**
	 * @param $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updateTrait($request): RedirectResponse
	{
		$entryId = $request->input('id');
		$entry = $this->xPanel->getEntry($entryId);
		
		// Add the 'fields' column's fake fields
		$fields = $entry->fields ?? [];
		$fields = JsonUtils::jsonToArray($fields);
		$this->addField($fields);
		
		$this->data['entry'] = $entry;
		
		return parent::updateCrud($request);
	}
	
	/**
	 * Is it a multi-fields adding?
	 *
	 * @param $crudFields
	 * @return void
	 */
	public function addFields($crudFields): void
	{
		// $firstElement = $crudFields[0] ?? null;
		// Get first $crudFields element
		$firstElement = reset($crudFields);
		$isMultiFields = is_array($firstElement);
		
		if (!$isMultiFields) {
			$this->addField($crudFields);
		}
		
		foreach ($crudFields as $field) {
			if (!is_array($field)) continue;
			
			try {
				if (!array_key_exists('fake', $field)) {
					$field['fake'] = true;
				}
				if (!array_key_exists('store_in', $field)) {
					$field['store_in'] = 'field_values';
				}
				
				$this->addField($field);
			} catch (Throwable $e) {
			}
		}
	}
	
	/**
	 * Add fake fields as an array of the default JSON
	 *
	 * @param $crudField
	 * @return void
	 */
	public function addField($crudField): void
	{
		// $firstElement = $crudField[0] ?? null;
		// Get first $crudField element
		$firstElement = reset($crudField);
		$isMultiFields = is_array($firstElement);
		
		// Is a multi-fields settings
		if ($isMultiFields) {
			$this->addFields($crudField);
		} else {
			// Is a one field settings (with valid json data)
			if (isset($crudField['name'])) {
				if (isset($crudField['label'])) {
					if (isset($crudField['autoTrans'])) {
						if (isset($crudField['plugin'])) {
							$crudField['label'] = trans($crudField['plugin'] . '::messages.' . $crudField['label']);
						} else {
							$crudField['label'] = trans('admin.' . $crudField['label']);
						}
					}
				}
				
				if (isset($crudField['hint'])) {
					if (isset($crudField['autoTrans'])) {
						$checkClearedHintContent = trim(strip_tags($crudField['hint']));
						if (!empty($checkClearedHintContent)) {
							if (isset($crudField['plugin'])) {
								$crudField['hint'] = trans($crudField['plugin'] . '::messages.' . $crudField['hint']);
							} else {
								$crudField['hint'] = trans('admin.' . $crudField['hint']);
							}
						}
					}
					$crudField['hint'] = str_replace('{adminUrl}', urlGen()->adminUrl(), $crudField['hint']);
				}
				
				if (isset($crudField['type']) && $crudField['type'] == 'custom_html') {
					if (isset($crudField['autoTrans'])) {
						$checkClearedValueContent = trim(strip_tags($crudField['value']));
						if (!empty($checkClearedValueContent)) {
							$crudField['value'] = trans('admin.' . $crudField['value']);
						}
					}
					$crudField['value'] = str_replace('{adminUrl}', urlGen()->adminUrl(), $crudField['value']);
				}
			} else {
				// Is a one field settings (without valid json data)
				$crudField = [
					'name'  => 'value',
					'label' => 'Value',
					'type'  => 'text',
				];
			}
			
			// Add the fake field to xPanel
			$this->xPanel->addField($crudField);
		}
	}
}
