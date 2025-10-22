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

class PictureResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\Picture $this */
		if (!isset($this->id)) return [];
		
		$entity = [
			'id' => $this->id,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$defaultPicture = config('larapen.media.picture');
		$defaultPictureUrl = thumbParam($defaultPicture)->url();
		$entity['url'] = [
			'full'   => $this->file_url ?? $defaultPictureUrl,
			'small'  => $this->file_url_small ?? $defaultPictureUrl,
			'medium' => $this->file_url_medium ?? $defaultPictureUrl,
			'large'  => $this->file_url_large ?? $defaultPictureUrl,
		];
		
		$isWebpFormatEnabled = (config('settings.optimization.webp_format') == '1');
		if ($isWebpFormatEnabled) {
			$entity['url']['webp'] = [
				'full'   => $this->webp_file_url ?? null,
				'small'  => $this->webp_file_url_small ?? null,
				'medium' => $this->webp_file_url_medium ?? null,
				'large'  => $this->webp_file_url_large ?? null,
			];
		}
		
		if (in_array('post', $this->embed)) {
			$entity['post'] = new PostResource($this->whenLoaded('post'), $this->params);
		}
		
		return $entity;
	}
}
