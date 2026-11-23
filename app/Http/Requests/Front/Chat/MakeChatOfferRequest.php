<?php

namespace App\Http\Requests\Front\Chat;

use App\Services\Chat\ChatOffers;
use Illuminate\Foundation\Http\FormRequest;

/*
 * An offer on a listing (adniro): an amount in the listing's currency. Who may make it, and on which
 * listing, is checked by the controller (ChatOfferController).
 */
class MakeChatOfferRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	public function rules(): array
	{
		return [
			// Digits with at most two decimals: the browser has already turned "1 200,50" into "1200.50"
			'amount'      => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/', 'numeric', 'gt:0', 'max:' . ChatOffers::AMOUNT_MAX],
			'client_uuid' => ['nullable', 'uuid'],
			// The conversation a seller answers in with their own price
			'thread'      => ['nullable', 'integer', 'min:1'],
		];
	}

	public function messages(): array
	{
		$message = trans('chat.error_offer_amount');

		return ['amount.required' => $message, 'amount.regex' => $message, 'amount.numeric' => $message, 'amount.gt' => $message, 'amount.max' => $message];
	}
}
