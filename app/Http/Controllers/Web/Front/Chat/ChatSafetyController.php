<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Chat\ReportChatRequest;
use App\Models\Chat\ThreadReport;
use App\Models\Chat\UserBlock;
use App\Models\User;
use App\Notifications\ChatReported;
use App\Services\Chat\ChatAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/*
 * Chat safety (adniro): block or unblock a member, and report a conversation to the admins.
 */
class ChatSafetyController extends Controller
{
	public function block(Request $request, int $user): JsonResponse
	{
		$userId = (int)$request->user()->id;
		abort_if($user === $userId, 422, trans('chat.error_block_self'));
		abort_unless(User::query()->withoutGlobalScopes()->whereKey($user)->exists(), 404);

		UserBlock::query()->firstOrCreate(['blocker_id' => $userId, 'blocked_id' => $user]);

		return response()->json(['blocked' => true]);
	}

	public function unblock(Request $request, int $user): JsonResponse
	{
		UserBlock::query()->where('blocker_id', (int)$request->user()->id)->where('blocked_id', $user)->delete();

		return response()->json(['blocked' => false]);
	}

	public function report(ReportChatRequest $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);

		// One open report per member and conversation: sending again updates it
		$report = ThreadReport::query()->updateOrCreate(
			['thread_id' => $chatThread->id, 'reporter_id' => $userId, 'status' => ThreadReport::STATUS_OPEN],
			['reason' => $request->input('reason'), 'note' => $request->input('note')],
		);

		ThreadReport::forgetOpenCount();
		$this->tellAdmins($report);

		return response()->json(['reported' => true], 201);
	}

	/** The report is saved first; a mail problem never loses it. */
	private function tellAdmins(ThreadReport $report): void
	{
		$adminEmail = config('settings.app.email');
		if (empty($adminEmail)) {
			return;
		}

		try {
			Notification::route('mail', $adminEmail)->notify(new ChatReported($report));
		} catch (Throwable $e) {
			Log::warning('Chat: report email failed', ['report' => $report->id, 'error' => $e->getMessage()]);
		}
	}
}
