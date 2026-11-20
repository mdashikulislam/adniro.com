<?php

namespace App\Services\Ai\Support;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Rules\RejectEmptyString;
use Illuminate\Support\Facades\Validator;

/*
 * Validates an AI answer against the request's JSON schema (adniro:ai).
 *
 * Built on Illuminate\JsonSchema\JsonSchema::rules(), adjusted to JSON schema meaning:
 * "required" means the key is present, so an empty list or an empty string is still valid,
 * at the top level and in nested objects.
 */

class SchemaRules
{
	public static function rules(array $schema): array
	{
		$rules = JsonSchema::rules($schema);
		
		foreach ($rules as $field => $fieldRules) {
			$fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', (string)$fieldRules);
			
			// Nested keys are already required by "required_array_keys"; "filled" would also reject
			// an empty string or list, which JSON schema allows
			$fieldRules = array_values(array_filter(
				$fieldRules,
				fn ($rule) => !$rule instanceof RejectEmptyString && $rule !== 'filled'
			));
			$fieldRules = array_map(fn ($rule) => $rule === 'required' ? 'present' : $rule, $fieldRules);
			
			$rules[$field] = $fieldRules;
		}
		
		return $rules;
	}
	
	/**
	 * @return string|null Null when valid, otherwise the first validation error
	 */
	public static function firstError(array $data, array $schema): ?string
	{
		$validator = Validator::make($data, self::rules($schema));
		
		return $validator->fails() ? (string)$validator->errors()->first() : null;
	}
}
