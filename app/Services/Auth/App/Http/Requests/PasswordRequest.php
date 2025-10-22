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

namespace App\Services\Auth\App\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasPasswordInput;
use App\Rules\CurrentPasswordRule;

class PasswordRequest extends Request
{
	use HasPasswordInput;
	
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize(): bool
	{
		$guard = getAuthGuard();
		
		return auth($guard)->check();
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		/** @var \App\Models\User $authUser */
		$authUser = auth(getAuthGuard())->user();
		
		$currentPasswordRules = !empty($authUser->password)
			? ['required', 'string']
			: ['nullable', 'required_with:new_password'];
		$currentPasswordRules[] = new CurrentPasswordRule($authUser);
		
		$rules = [
			'current_password' => $currentPasswordRules,
			'new_password'     => ['required', 'confirmed'],
		];
		
		// new_password
		return $this->passwordRules($rules, 'new_password');
	}
}
