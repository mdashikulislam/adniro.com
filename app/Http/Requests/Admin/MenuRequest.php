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

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class MenuRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$updateMethods = ['PUT', 'PATCH', 'UPDATE'];
		
		$uniqueLocation = 'unique:menus,location';
		if (in_array($this->method(), $updateMethods)) {
			$uniqueLocation = Rule::unique('menus', 'location')->ignore($this->route('menu'));
		}
		
		return [
			'name'        => ['required', 'string', 'max:255'],
			'location'    => ['required', 'string', 'max:255', $uniqueLocation],
			'description' => ['nullable', 'string', 'max:1000'],
			'lft'         => ['nullable', 'integer'],
			'rgt'         => ['nullable', 'integer'],
			'depth'       => ['nullable', 'integer'],
			'active'      => ['boolean'],
		];
	}
}
