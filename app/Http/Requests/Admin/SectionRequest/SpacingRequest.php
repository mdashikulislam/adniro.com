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

namespace App\Http\Requests\Admin\SectionRequest;

/*
 * Use request() instead of $this since this form request can be called from another
 */

class SpacingRequest extends BaseRequest
{
	public ?string $uniqueMarginsMessage = null;
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$request = request();
		
		// Define base validation rules for margin configuration structure
		$rules = [
			'margins'              => ['nullable', 'array'],
			'margins.*.side'       => ['string'],
			'margins.*.breakpoint' => ['nullable', 'string'],
			'margins.*.size'       => ['integer'],
		];
		
		// Safely retrieve and merge new margin configurations from the request
		$inputMargins = $request->input('margins');
		$inputMargins = is_array($inputMargins) ? $inputMargins : [];
		
		// Create a function to generate unique identifiers for margin configurations
		// This combines side + breakpoint to detect duplicate margin settings
		$fnCreateUniqueIdentifier = function ($item) {
			$side = $item['side'] ?? '';
			$breakpoint = $item['breakpoint'] ?? '';
			
			return $side . $breakpoint;
		};
		
		// Validate that the margin configurations are unique
		// If duplicates exist after merging, add a validation rule to fail the request
		$hasDuplicates = collect($inputMargins)->duplicates($fnCreateUniqueIdentifier)->isNotEmpty();
		if ($hasDuplicates) {
			$rules['unique_margins'] = ['required'];
			$this->uniqueMarginsMessage = 'Unique margins are required.';
		}
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		if (!empty($this->uniqueMarginsMessage)) {
			$messages['unique_margins'] = $this->uniqueMarginsMessage;
		}
		
		return $this->mergeMessages($messages);
	}
	
	/**
	 * @return array
	 */
	public function attributes(): array
	{
		$request = request();
		
		$attributes = [
			'margins'              => 'Margin',
			'margins.*.side'       => 'Margin Side',
			'margins.*.breakpoint' => 'Margin Breakpoint',
			'margins.*.size'       => 'Margin Size',
		];
		
		// Dynamic labels for margins
		$margins = $request->input('margins');
		if (!empty($margins)) {
			foreach ($margins as $key => $value) {
				$index = $key + 1;
				$attributes["margins.{$key}.side"] = "Margin #$index Side";
				$attributes["margins.{$key}.breakpoint"] = "Margin #$index Breakpoint";
				$attributes["margins.{$key}.size"] = "Margin #$index Size";
			}
		}
		
		return $this->mergeAttributes($attributes);
	}
}
