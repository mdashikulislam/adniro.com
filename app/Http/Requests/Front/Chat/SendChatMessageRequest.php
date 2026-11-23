<?php

namespace App\Http\Requests\Front\Chat;

use App\Rules\BlacklistWordRule;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatPhotos;
use Illuminate\Foundation\Http\FormRequest;

/*
 * A chat message (adniro): text, or one photo. Text is stored exactly as written ("Can you do <500?" keeps
 * its "<500?") and is only ever shown as plain text, never as HTML: the chat writes it with textContent and
 * emails escape it. Who may send is checked by the controller (ChatAccess).
 */
class SendChatMessageRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	protected function prepareForValidation(): void
	{
		if (!$this->filled('body')) {
			return;
		}

		$this->merge(['body' => trim((string)$this->input('body'))]);
	}

	public function rules(): array
	{
		return [
			'body'        => ['required_without:photo', 'nullable', 'string', 'max:' . ChatMessages::BODY_MAX, new BlacklistWordRule()],
			'photo'       => [
				'nullable', 'file', 'mimes:' . ChatPhotos::MIMES, 'max:' . ChatPhotos::MAX_UPLOAD_KB,
				'dimensions:max_width=' . ChatPhotos::MAX_SIDE . ',max_height=' . ChatPhotos::MAX_SIDE,
			],
			'client_uuid' => ['nullable', 'uuid'],
			'reply_to'    => ['nullable', 'integer', 'min:1'],
		];
	}

	public function messages(): array
	{
		return [
			'body.required_without' => trans('chat.error_empty'),
			'body.max'              => trans('chat.error_too_long', ['max' => ChatMessages::BODY_MAX]),
			'photo.mimes'           => trans('chat.error_photo_type'),
			'photo.max'             => trans('chat.error_photo_size'),
			'photo.dimensions'      => trans('chat.error_photo_size'),
		];
	}
}
