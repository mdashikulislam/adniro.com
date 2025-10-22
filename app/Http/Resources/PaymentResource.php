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

class PaymentResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\Payment $this */
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
		
		$appendedColumns = $this->getAppends();
		foreach ($appendedColumns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		if (array_key_exists('canceled_at_formatted', $entity) && $entity['canceled_at_formatted'] === null) {
			unset($entity['canceled_at_formatted']);
		}
		if (array_key_exists('refunded_at_formatted', $entity) && $entity['refunded_at_formatted'] === null) {
			unset($entity['refunded_at_formatted']);
		}
		if (array_key_exists('remaining_posts', $entity) && $entity['remaining_posts'] === null) {
			unset($entity['remaining_posts']);
		}
		
		$payableType = $this->payable_type ?? '';
		$isPromoting = (str_ends_with($payableType, 'Post'));
		$isSubscripting = (str_ends_with($payableType, 'User'));
		
		if (in_array('payable', $this->embed)) {
			if ($isPromoting) {
				$entity['payable'] = new PostResource($this->whenLoaded('payable'), $this->params);
			}
			if ($isSubscripting) {
				$entity['payable'] = new UserResource($this->whenLoaded('payable'), $this->params);
			}
		}
		if (in_array('package', $this->embed)) {
			$entity['package'] = new PackageResource($this->whenLoaded('package'), $this->params);
		}
		if (in_array('paymentMethod', $this->embed)) {
			$entity['paymentMethod'] = new PaymentMethodResource($this->whenLoaded('paymentMethod'), $this->params);
		}
		
		return $entity;
	}
}
