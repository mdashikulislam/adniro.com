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

class MenuItemResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\MenuItem $this */
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
		
		if (in_array('menu', $this->embed)) {
			$menu = $this->whenLoaded('menu');
			$entity['menu'] = new MenuResource($menu, $this->params);
		}
		if (in_array('parent', $this->embed)) {
			$parent = $this->whenLoaded('parent');
			$entity['parent'] = new static($parent, $this->params);
		}
		if (in_array('ancestors', $this->embed)) {
			$ancestors = $this->whenLoaded('ancestors');
			$ancestorsCollection = new EntityCollection(MenuItemResource::class, $ancestors, $this->params);
			$entity['ancestors'] = $ancestorsCollection->toArray(request(), true);
		}
		if (in_array('children', $this->embed)) {
			$children = $this->whenLoaded('children');
			$childrenCollection = new EntityCollection(MenuItemResource::class, $children, $this->params);
			$entity['children'] = $childrenCollection->toArray(request(), true);
			
			$childrenCount = $this->when(isset($this->children_count), $this->children_count);
			$entity['children_count'] = $childrenCount;
		}
		
		return $entity;
	}
}
