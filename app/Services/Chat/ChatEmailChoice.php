<?php

namespace App\Services\Chat;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/*
 * A member's choice about the unread-messages email (adniro): on by default, turned off or on from the chat
 * page, or turned off from the link in the email itself, which works without signing in.
 */
class ChatEmailChoice
{
	public const OFF_ROUTE = 'chat.emails.off';

	public static function isOn(User $user): bool
	{
		return (int)($user->chat_emails ?? 1) === 1;
	}

	/** Written with the query builder: the choice is not part of the member's profile form, and no observer runs. */
	public static function set(int $userId, bool $isOn): void
	{
		DB::table((new User())->getTable())->where('id', $userId)->update(['chat_emails' => $isOn]);
	}

	/**
	 * The address in the email that turns the emails off for this member. It is signed, so nobody can turn
	 * them off for somebody else, and it does not expire: old emails keep a working link. Only the path is
	 * signed, so it still checks out when the site is reached through a proxy or another host name.
	 */
	public static function offUrl(int $userId): string
	{
		return url(URL::signedRoute(self::OFF_ROUTE, ['user' => $userId], null, false));
	}
}
