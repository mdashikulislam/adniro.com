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

namespace App\Http\Resources;

use App\Enums\Continent;
use Illuminate\Http\Request;

class CountryResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\Country $this */
		if (!isset($this->code)) return [];
		
		$entity = [
			'code' => $this->code,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$appendedColumns = $this->getAppends();
		foreach ($appendedColumns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		if (in_array('currency', $this->embed)) {
			$entity['currency'] = new CurrencyResource($this->whenLoaded('currency'), $this->params);
		}
		if (in_array('continent', $this->embed)) {
			if (!empty($this->continent_code)) {
				$entity['continent'] = Continent::find($this->continent_code);
			}
		}
		
		return $entity;
	}
}
