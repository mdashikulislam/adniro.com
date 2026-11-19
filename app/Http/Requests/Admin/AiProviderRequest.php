<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/*
 * AI provider form (adniro:ai).
 */

class AiProviderRequest extends Request
{
	public function rules(): array
	{
		$drivers = array_keys((array)config('ai_features.drivers'));
		$urlRequired = (bool)config('ai_features.drivers.' . $this->input('driver') . '.url_required', false);
		
		return [
			'name'     => ['required', 'string', 'min:2', 'max:100'],
			'driver'   => ['required', Rule::in($drivers)],
			'api_key'  => ['nullable', 'string', 'max:500'],
			'base_url' => [$urlRequired ? 'required' : 'nullable', 'url:http,https', 'max:255'],
			'active'   => ['nullable', 'boolean'],
		];
	}
	
	public function attributes(): array
	{
		return [
			'name'     => trans('ai.field_name'),
			'driver'   => trans('ai.field_driver'),
			'api_key'  => trans('ai.field_api_key'),
			'base_url' => trans('ai.field_base_url'),
		];
	}
}
