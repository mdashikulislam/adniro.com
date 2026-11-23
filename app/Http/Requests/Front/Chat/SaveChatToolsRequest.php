<?php

namespace App\Http\Requests\Front\Chat;

use App\Rules\BlacklistWordRule;
use App\Services\Chat\ChatTools;
use Illuminate\Foundation\Http\FormRequest;

/*
 * A member's away message and saved replies (adniro). They are plain text, kept as written, and are sent
 * later as ordinary chat messages, which are only ever shown as text.
 */
class SaveChatToolsRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	protected function prepareForValidation(): void
	{
		$replies = array_filter(
			array_map(fn ($reply) => is_string($reply) ? trim($reply) : $reply, (array)$this->input('saved_replies', [])),
			fn ($reply) => $reply !== '' && $reply !== null,
		);

		$this->merge([
			'away_message'  => trim((string)$this->input('away_message')),
			'saved_replies' => array_values($replies),
		]);
	}

	public function rules(): array
	{
		return [
			'away_on'         => ['required', 'boolean'],
			'away_message'    => ['required_if_accepted:away_on', 'nullable', 'string', 'max:' . ChatTools::TEXT_MAX, new BlacklistWordRule()],
			'saved_replies'   => ['present', 'array', 'max:' . ChatTools::SAVED_REPLIES_MAX],
			'saved_replies.*' => ['string', 'max:' . ChatTools::TEXT_MAX, new BlacklistWordRule()],
		];
	}

	public function messages(): array
	{
		return [
			'away_message.required_if_accepted' => trans('chat.error_away_empty'),
			'away_message.max'                  => trans('chat.error_tools_long', ['max' => ChatTools::TEXT_MAX]),
			'saved_replies.max'                 => trans('chat.error_saved_many', ['max' => ChatTools::SAVED_REPLIES_MAX]),
			'saved_replies.*.max'               => trans('chat.error_tools_long', ['max' => ChatTools::TEXT_MAX]),
		];
	}
}
