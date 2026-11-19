<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/*
 * AI model form (adniro:ai).
 */

class AiModelRequest extends Request
{
	public function rules(): array
	{
		$id = $this->route('ai_model') ?? $this->input('id');
		
		return [
			'ai_provider_id'             => ['required', 'integer', 'exists:ai_providers,id'],
			'model_id'                   => [
				'required', 'string', 'max:191', 'regex:/^[A-Za-z0-9._:\/@+\-]+$/',
				Rule::unique('ai_models', 'model_id')
					->where('ai_provider_id', $this->input('ai_provider_id'))
					->ignore($id),
			],
			'label'                      => ['nullable', 'string', 'max:191'],
			'supports_vision'            => ['nullable', 'boolean'],
			'supports_structured_output' => ['nullable', 'boolean'],
			'supports_transcription'     => ['nullable', 'boolean'],
			'input_price_per_mtok'       => ['nullable', 'numeric', 'min:0', 'max:100000'],
			'output_price_per_mtok'      => ['nullable', 'numeric', 'min:0', 'max:100000'],
			'options'                    => [
				'nullable', 'string', 'max:5000',
				function (string $attribute, mixed $value, \Closure $fail) {
					if (blank($value)) {
						return;
					}
					$decoded = json_decode((string)$value, true);
					if (!is_array($decoded) || array_is_list($decoded) && !empty($decoded)) {
						$fail(trans('ai.field_options_invalid'));
					}
				},
			],
			'active'                     => ['nullable', 'boolean'],
		];
	}
	
	public function messages(): array
	{
		return [
			'model_id.unique' => trans('ai.model_unique'),
		];
	}
	
	public function attributes(): array
	{
		return [
			'ai_provider_id' => trans('ai.field_provider'),
			'model_id'       => trans('ai.field_model_id'),
		];
	}
}
