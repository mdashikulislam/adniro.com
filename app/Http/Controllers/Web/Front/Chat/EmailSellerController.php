<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Chat\EmailSellerRequest;
use App\Models\Post;
use App\Notifications\ListingEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/*
 * "Email the seller" (adniro): a listing posted without a member account has nobody to chat with, so a
 * signed-in member's message is sent to the listing's email address. The site sends it: the seller's address
 * is never shown; the member's name and address go with it so the seller can reply. Nothing is stored.
 * Where mail goes through a queue, a failed send is reported by the queue (failed jobs), not to the member.
 */
class EmailSellerController extends Controller
{
	public function __invoke(EmailSellerRequest $request, int $post): JsonResponse
	{
		$listing = Post::query()->unarchived()->findOrFail($post);
		$sender = $request->user();

		// A listing with a member account is chatted with; one without an email address cannot be written to
		if (!empty($listing->user_id) || empty($listing->email) || !isVerifiedPost($listing)) {
			return response()->json(['message' => trans('chat.error_email_seller_unavailable')], 403);
		}
		if (empty($sender->email)) {
			return response()->json(['message' => trans('chat.error_email_seller_no_address')], 422);
		}

		try {
			Notification::route('mail', $listing->email)->notify(new ListingEnquiry($listing, $sender, (string)$request->input('message')));
		} catch (Throwable $e) {
			Log::warning('Email the seller: sending failed', ['post' => $listing->id, 'sender' => $sender->id, 'error' => $e->getMessage()]);

			return response()->json(['message' => trans('chat.email_seller_failed')], 502);
		}

		return response()->json(['sent' => true], 201);
	}
}
