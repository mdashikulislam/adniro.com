<?php

namespace App\Http\Controllers\Web\Admin;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ThreadReport;
use App\Services\Chat\ChatPhotos;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * Admin > Users > Chat reports (adniro): the list of reported conversations, one report with the
 * conversation it is about, and closing it.
 *
 * Action names (reports, report, close, reopen, photo) are deliberately not the standard CRUD names, so the
 * panel's route-permission parser adds nothing; access is checked by the ChatReportAccess middleware.
 * A conversation is shown in full, including messages their sender took back: taking a message back hides it
 * from the two people, never from a report.
 */

class ChatReportController extends Controller
{
	public const MESSAGES_SHOWN = 200;
	private const PER_PAGE = 25;
	
	public function reports(Request $request)
	{
		$status = in_array($request->query('status'), ThreadReport::STATUSES, true) ? $request->query('status') : ThreadReport::STATUS_OPEN;
		
		$reports = ThreadReport::query()
			->where('status', $status)
			->with(['reporter', 'thread.participants.user'])
			->orderByDesc('id')
			->paginate(self::PER_PAGE)
			->withQueryString();
		
		$counts = ThreadReport::query()->groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status');
		
		return view('admin.chat.reports.list', ['reports' => $reports, 'status' => $status, 'counts' => $counts]);
	}
	
	public function report($id)
	{
		$report = $this->findReport($id);
		$reportedId = $report->reportedUserId();
		
		// The latest messages, shown oldest first
		$messages = ChatMessage::query()
			->where('thread_id', $report->thread_id)
			->with('post')
			->orderByDesc('id')
			->limit(self::MESSAGES_SHOWN)
			->get()
			->reverse()
			->values();
		
		return view('admin.chat.reports.show', [
			'report'       => $report,
			'reported'     => $report->thread?->participantFor($reportedId)?->user,
			'reportedId'   => $reportedId,
			'messages'     => $messages,
			'totalCount'   => ChatMessage::query()->where('thread_id', $report->thread_id)->count(),
			'otherReports' => $this->otherReportsAbout($reportedId, (int)$report->id),
		]);
	}
	
	public function close($id)
	{
		$report = $this->findReport($id);
		$report->update(['status' => ThreadReport::STATUS_CLOSED, 'closed_by' => auth()->id(), 'closed_at' => now()]);
		ThreadReport::forgetOpenCount();
		
		notification(trans('chat_admin.report_closed', ['id' => $report->id]), 'success');
		
		return redirect()->to(urlGen()->adminUrl('chat-reports'));
	}
	
	public function reopen($id)
	{
		$report = $this->findReport($id);
		$report->update(['status' => ThreadReport::STATUS_OPEN, 'closed_by' => null, 'closed_at' => null]);
		ThreadReport::forgetOpenCount();
		
		notification(trans('chat_admin.report_reopened', ['id' => $report->id]), 'info');
		
		return redirect()->back();
	}
	
	/** A photo of the reported conversation. Members only reach photos through the chat's own route. */
	public function photo($id, $message): StreamedResponse
	{
		$report = $this->findReport($id);
		$chatMessage = ChatMessage::query()->where('thread_id', $report->thread_id)->findOrFail((int)$message);
		abort_unless($chatMessage->isImage() && ChatPhotos::isChatPath($chatMessage->file_path), 404);
		
		$path = ChatPhotos::thumbPath($chatMessage->file_path);
		abort_unless(ChatPhotos::disk()->exists($path), 404);
		
		return ChatPhotos::disk()->response($path, null, [
			'Content-Type'           => 'image/jpeg',
			'Cache-Control'          => 'private, no-store',
			'X-Content-Type-Options' => 'nosniff',
		]);
	}
	
	/** How many other reports, from other people, are about the same member. */
	private function otherReportsAbout(int $reportedId, int $exceptReportId): int
	{
		if ($reportedId === 0) {
			return 0;
		}
		
		return ThreadReport::query()
			->whereKeyNot($exceptReportId)
			->where('reporter_id', '!=', $reportedId)
			->whereHas('thread.participants', fn ($participants) => $participants->where('user_id', $reportedId))
			->count();
	}
	
	private function findReport($id): ThreadReport
	{
		$report = ThreadReport::query()->with(['reporter', 'closer', 'thread.participants.user'])->find((int)$id);
		abort_if(empty($report), 404);
		
		return $report;
	}
}
