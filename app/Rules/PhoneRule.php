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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneRule implements ValidationRule
{
	public ?string $countryCode;
	
	public function __construct(?string $countryCode = null)
	{
		$this->countryCode = $countryCode;
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail(trans('validation.phone'));
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		/*
		$smsSendingIsRequired = (
			isPhoneAsAuthFieldEnabled()
			&& (
				config('settings.sms.phone_verification') == '1'
				|| config('settings.sms.confirmation') == '1'
				|| config('settings.sms.messenger_notifications') == '1'
			)
		);
		*/
		$phoneValidator = castToString(config('settings.sms.phone_validator'));
		if ($phoneValidator == 'none' || empty($phoneValidator)) return true;
		
		if (!function_exists($phoneValidator)) return false;
		
		$value = castToString($value);
		$countryCode = $this->countryCode ?? getPhoneCountry();
		
		return $phoneValidator($value, $countryCode);
	}
}
