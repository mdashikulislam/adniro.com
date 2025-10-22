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

namespace App\Http\Requests\Admin\SettingRequest;

/*
 * Use request() instead of $this since this form request can be called from another
 */

class AuthRequest extends BaseRequest
{
	private ?string $intlExtensionInstallationMessage;
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$request = request();
		
		$rules = [];
		
		// Password validator
		$rules['password_min_length'] = ['required', 'integer', 'min:4', 'lte:password_max_length'];
		$rules['password_max_length'] = ['required', 'integer', 'max:100', 'gte:password_min_length'];
		
		// Email address validator
		if (
			(
				$request->filled('email_validator_dns')
				|| $request->filled('email_validator_spoof')
			)
			&& !extension_loaded('intl')
		) {
			$rules['intl_extension_installation'] = ['required'];
			$this->intlExtensionInstallationMessage = trans('admin.intl_extension_missing_error_message_for_email_validation');
		}
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		if (!empty($this->intlExtensionInstallationMessage)) {
			$messages['intl_extension_installation'] = $this->intlExtensionInstallationMessage;
		}
		
		return $this->mergeMessages($messages);
	}
	
	/**
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [
			'password_min_length'   => trans('admin.password_min_length_label'),
			'password_max_length'   => trans('admin.password_max_length_label'),
			'email_validator_dns'   => trans('admin.email_validator_dns_label'),
			'email_validator_spoof' => trans('admin.email_validator_spoof_label'),
		];
		
		return $this->mergeAttributes($attributes);
	}
}
