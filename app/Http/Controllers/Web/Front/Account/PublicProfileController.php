<?php

namespace App\Http\Controllers\Web\Front\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/*
 * "View public profile" in the account menu (adniro): one address for every member, which takes them to
 * their own public profile. A menu entry cannot hold each member's own link, and this way the menu stays
 * the same for everyone and can be kept in the cache.
 */
class PublicProfileController extends Controller
{
	public function __invoke(Request $request): RedirectResponse
	{
		$profileUrl = urlGen()->user($request->user());

		return redirect()->to($profileUrl ?: route('account.profile'));
	}
}
