<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatMessage;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatPhotos;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * A photo sent in a chat (adniro), for the conversation's participants only. Photos are on the private disk:
 * this route is the only way to open one.
 */
class ChatFileController extends Controller
{
	private const CACHE_SECONDS = 86400;

	public function __invoke(Request $request, int $message): StreamedResponse
	{
		$chatMessage = ChatMessage::query()->findOrFail($message);
		abort_unless(ChatAccess::isParticipant((int)$chatMessage->thread_id, (int)$request->user()->id), 404);
		abort_unless($chatMessage->isImage() && ChatPhotos::isChatPath($chatMessage->file_path), 404);

		$path = $request->query('size') === 'thumb' ? ChatPhotos::thumbPath($chatMessage->file_path) : $chatMessage->file_path;
		abort_unless(ChatPhotos::disk()->exists($path), 404);

		return ChatPhotos::disk()->response($path, null, [
			'Content-Type'           => 'image/jpeg',
			'Cache-Control'          => 'private, max-age=' . self::CACHE_SECONDS,
			'X-Content-Type-Options' => 'nosniff',
		]);
	}
}
