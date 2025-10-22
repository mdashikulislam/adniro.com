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

use Illuminate\Http\Request;

class CityResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\City $this */
		if (!isset($this->id)) return [];
		
		$entity = [
			'id' => $this->id,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$appendedColumns = $this->getAppends();
		foreach ($appendedColumns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$entity['posts_count'] = $this->posts_count ?? 0;
		
		if (in_array('country', $this->embed)) {
			$entity['country'] = new CountryResource($this->whenLoaded('country'), $this->params);
		}
		if (in_array('subAdmin1', $this->embed)) {
			$entity['subAdmin1'] = new SubAdmin1Resource($this->whenLoaded('subAdmin1'), $this->params);
		}
		if (in_array('subAdmin2', $this->embed)) {
			$entity['subAdmin2'] = new SubAdmin2Resource($this->whenLoaded('subAdmin2'), $this->params);
		}
		
		return $entity;
	}
}
