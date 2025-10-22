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

namespace App\Http\Requests\Traits;

use App\Rules\BlacklistDomainRule;
use App\Rules\BlacklistEmailRule;
use Illuminate\Validation\Rule;

trait HasEmailInput
{
	/**
	 * Valid Email Address Rules
	 *
	 * @param array $rules
	 * @param string $field
	 * @return array
	 */
	protected function emailRules(array $rules = [], string $field = 'email'): array
	{
		if ($this->filled($field)) {
			if (isDemoEnv()) {
				if (isDemoEmailAddress($this->input($field))) {
					return $rules;
				}
			}
			
			$rule = Rule::email();
			
			if (config('settings.auth.email_validator_rfc')) {
				$rule->rfcCompliant();
			}
			if (config('settings.auth.email_validator_strict')) {
				$rule->rfcCompliant(strict: true);
			}
			if (extension_loaded('intl')) {
				if (config('settings.auth.email_validator_dns')) {
					$rule->validateMxRecord();
				}
				if (config('settings.auth.email_validator_spoof')) {
					$rule->preventSpoofing();
				}
			}
			if (config('settings.auth.email_validator_filter')) {
				$rule->withNativeValidation(allowUnicode: true);
			}
			
			$rules[$field][] = $rule;
			$rules[$field][] = 'max:100';
			$rules[$field][] = new BlacklistEmailRule();
			$rules[$field][] = new BlacklistDomainRule();
		}
		
		return $rules;
	}
}
