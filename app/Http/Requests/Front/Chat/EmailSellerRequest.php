<?php

namespace App\Http\Requests\Front\Chat;

use App\Rules\BlacklistWordRule;
use Illuminate\Foundation\Http\FormRequest;

/*
 * A message to the seller of a listing posted without a member account (adniro), sent by email.
 * Who may send, and to which listing, is checked by the controller.
 */
class EmailSellerRequest extends FormRequest
{
	public const MESSAGE_MIN = 10;
	public const MESSAGE_MAX = 1000;

	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	protected function prepareForValidation(): void
	{
		if ($this->filled('message')) {
			$this->merge(['message' => trim((string)$this->input('message'))]);
		}
	}

	public function rules(): array
	{
		return [
			'message' => ['required', 'string', 'min:' . self::MESSAGE_MIN, 'max:' . self::MESSAGE_MAX, new BlacklistWordRule()],
		];
	}

	public function messages(): array
	{
		return [
			'message.required' => trans('chat.error_empty_email'),
			'message.min'      => trans('chat.error_empty_email'),
			'message.max'      => trans('chat.error_too_long', ['max' => self::MESSAGE_MAX]),
		];
	}
}
