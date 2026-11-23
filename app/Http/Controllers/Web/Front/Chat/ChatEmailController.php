<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Web\Front\FrontController;
use App\Models\User;
use App\Services\Chat\ChatEmailChoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

/*
 * The unread-messages email, on or off (adniro): the switch on the chat page, and the link in the email,
 * which needs no sign-in (its address is signed for one member).
 */
class ChatEmailController extends FrontController
{
	/** The chat page's switch. */
	public function update(Request $request): JsonResponse
	{
		$validated = $request->validate(['emails' => ['required', 'boolean']]);
		$isOn = (bool)$validated['emails'];
		ChatEmailChoice::set((int)$request->user()->id, $isOn);

		return response()->json(['emails' => $isOn]);
	}

	/**
	 * The link in the email opens a page that asks first: mail scanners open every link of an email, and
	 * must not turn anything off by doing so.
	 */
	public function confirm(int $user): View
	{
		return $this->page($user, false);
	}

	public function turnOff(int $user): View
	{
		ChatEmailChoice::set((int)$this->member($user)->id, false);

		return $this->page($user, true);
	}

	private function page(int $userId, bool $isDone): View
	{
		$this->member($userId);
		MetaTag::set('title', trans('chat.emails_off_title'));
		MetaTag::set('description', trans('chat.emails_off_title'));

		return view('front.chat.emails-off', ['isDone' => $isDone, 'offUrl' => ChatEmailChoice::offUrl($userId)]);
	}

	private function member(int $userId): User
	{
		return User::query()->withoutGlobalScopes()->findOrFail($userId);
	}
}
