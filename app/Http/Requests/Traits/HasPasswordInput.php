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

use Illuminate\Validation\Rules\Password;

trait HasPasswordInput
{
	/**
	 * Valid Password Rules
	 *
	 * @param array $rules
	 * @param string $field
	 * @return array
	 */
	protected function passwordRules(array $rules = [], string $field = 'password'): array
	{
		if ($this->filled($field)) {
			$rule = Password::min(config('settings.auth.password_min_length', 6));
			
			if (config('settings.auth.password_letters_required')) {
				$rule->letters();
			}
			if (config('settings.auth.password_mixedCase_required')) {
				$rule->mixedCase();
			}
			if (config('settings.auth.password_numbers_required')) {
				$rule->numbers();
			}
			if (config('settings.auth.password_symbols_required')) {
				$rule->symbols();
			}
			if (config('settings.auth.password_uncompromised_required')) {
				$rule->uncompromised(config('settings.auth.password_uncompromised_threshold', 0));
			}
			
			$rules[$field][] = $rule;
			$rules[$field][] = 'max:' . config('settings.auth.password_max_length', 60);
		}
		
		return $rules;
	}
}
