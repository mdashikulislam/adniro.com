<?php

use App\Http\Controllers\Web\Front\Chat\ChatController;
use App\Http\Controllers\Web\Front\Chat\ChatEmailController;
use App\Http\Controllers\Web\Front\Chat\ChatFileController;
use App\Http\Controllers\Web\Front\Chat\ChatMessageController;
use App\Http\Controllers\Web\Front\Chat\ChatSafetyController;
use App\Http\Controllers\Web\Front\Chat\EmailSellerController;
use App\Services\Chat\ChatEmailChoice;
use Illuminate\Support\Facades\Route;

/*
 * adniro: live chat between a buyer and a seller. Members only. Every route that takes a conversation checks
 * that the member is one of its two participants (App\Services\Chat\ChatAccess).
 * The live channels and the send limits are in App\Providers\ChatServiceProvider.
 */
Route::middleware(['auth', 'twoFactor', 'banned.user', 'no.http.cache'])
	->prefix('chat')
	->where(['thread' => '[0-9]+', 'post' => '[0-9]+', 'message' => '[0-9]+', 'user' => '[0-9]+'])
	->group(function () {
		// Pages
		Route::get('/', [ChatController::class, 'index'])->name('chat.index');
		Route::get('{thread}', [ChatController::class, 'show'])->name('chat.show');
		Route::get('new/{post}', [ChatController::class, 'draft'])->name('chat.new');

		// The first message about a listing
		Route::post('listings/{post}', [ChatMessageController::class, 'start'])
			->middleware(['throttle:chat-start', 'throttle:chat-send'])
			->name('chat.start');

		// A listing posted without a member account has nobody to chat with: the message is emailed
		Route::post('email-seller/{post}', EmailSellerController::class)
			->middleware('throttle:chat-email-seller')
			->name('chat.email-seller');

		// Photos (private)
		Route::get('files/{message}', ChatFileController::class)->name('chat.file');

		// Data
		Route::prefix('api')->group(function () {
			Route::get('threads', [ChatMessageController::class, 'threads'])->name('chat.api.threads');
			Route::get('threads/{thread}', [ChatMessageController::class, 'thread'])->name('chat.api.thread');
			Route::get('threads/{thread}/messages', [ChatMessageController::class, 'messages'])->name('chat.api.messages');
			Route::post('threads/{thread}/messages', [ChatMessageController::class, 'send'])
				->middleware('throttle:chat-send')
				->name('chat.api.send');
			Route::post('threads/{thread}/read', [ChatMessageController::class, 'read'])
				->middleware('throttle:60,1')
				->name('chat.api.read');
			Route::get('unread', [ChatMessageController::class, 'unread'])->name('chat.api.unread');
			Route::put('settings', [ChatEmailController::class, 'update'])
				->middleware('throttle:30,1')
				->name('chat.api.settings');

			Route::post('users/{user}/block', [ChatSafetyController::class, 'block'])
				->middleware('throttle:30,1')
				->name('chat.api.block');
			Route::delete('users/{user}/block', [ChatSafetyController::class, 'unblock'])
				->middleware('throttle:30,1')
				->name('chat.api.unblock');
			Route::post('threads/{thread}/report', [ChatSafetyController::class, 'report'])
				->middleware('throttle:chat-report')
				->name('chat.api.report');
		});
	});

// The "stop these emails" link of the unread-messages email: no sign-in, the address is signed for one member.
// GET asks first (mail scanners open every link); POST turns the emails off.
Route::middleware(['signed:relative', 'throttle:30,1', 'no.http.cache'])
	->prefix('chat/emails/off')
	->where(['user' => '[0-9]+'])
	->group(function () {
		Route::get('{user}', [ChatEmailController::class, 'confirm'])->name(ChatEmailChoice::OFF_ROUTE);
		Route::post('{user}', [ChatEmailController::class, 'turnOff'])->name(ChatEmailChoice::OFF_ROUTE . '.confirm');
	});
