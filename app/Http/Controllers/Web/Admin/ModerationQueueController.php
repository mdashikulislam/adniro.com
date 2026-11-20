<?php

namespace App\Http\Controllers\Web\Admin;

use App\Models\Post;
use App\Models\PostModeration;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/*
 * Admin > Listings > AI review queue (adniro:ai).
 *
 * Action names (queue, history, rejectForm, approve, reject, rerun) are deliberately not the
 * standard CRUD names, so the panel's route-permission parser adds nothing; access is checked by
 * the ModerationQueueAccess middleware instead (super admin, or permission to edit listings).
 */

class ModerationQueueController extends Controller
{
	public const FILTERS = ['needs_review', 'rejected', 'pending', 'shadow', 'all'];
	
	public function queue(Request $request)
	{
		$filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'needs_review';
		$search = trim((string)$request->query('q', ''));
		
		$query = Post::query()->withoutGlobalScopes()->with('user');
		
		if ($filter === 'shadow') {
			// Live listings whose latest AI check would have held or rejected them
			$query->where('moderation_status', ModerationStatus::APPROVED)
				->whereExists(function ($sub) {
					$sub->select(DB::raw(1))
						->from('post_moderations as pm')
						->whereColumn('pm.post_id', 'posts.id')
						->where('pm.mode', 'shadow')
						->whereIn('pm.decided_status', [ModerationStatus::NEEDS_REVIEW, ModerationStatus::REJECTED])
						->whereRaw('pm.id = (select max(pm2.id) from post_moderations pm2 where pm2.post_id = posts.id and pm2.source in (?, ?))', ['ai', 'human']);
				});
		} else if ($filter === 'all') {
			$query->where('moderation_status', '!=', ModerationStatus::APPROVED);
		} else {
			$query->where('moderation_status', $filter);
		}
		
		if ($search !== '') {
			$query->where(function ($q) use ($search) {
				$q->where('title', 'like', '%' . $search . '%');
				if (ctype_digit($search)) {
					$q->orWhere('id', (int)$search);
				}
			});
		}
		
		$posts = $query->orderBy('updated_at')->orderBy('id')->paginate(25)->withQueryString();
		
		$decisions = PostModeration::query()
			->whereIn('post_id', $posts->pluck('id'))
			->orderByDesc('id')
			->get()
			->groupBy('post_id')
			->map(fn ($rows) => $rows->first());
		
		$counts = Post::query()->withoutGlobalScopes()
			->where('moderation_status', '!=', ModerationStatus::APPROVED)
			->groupBy('moderation_status')
			->selectRaw('moderation_status, count(*) as total')
			->pluck('total', 'moderation_status');
		
		return view('admin.ai.moderation.queue', [
			'posts'     => $posts,
			'decisions' => $decisions,
			'filter'    => $filter,
			'search'    => $search,
			'counts'    => $counts,
			'mode'      => app(ModerationService::class)->mode(),
		]);
	}
	
	public function history($id)
	{
		$post = $this->findPost($id);
		$rows = PostModeration::query()->with(['reviewer', 'usageLog'])->where('post_id', $post->id)->orderByDesc('id')->get();
		
		return view('admin.ai.moderation.history', ['post' => $post, 'rows' => $rows]);
	}
	
	public function rejectForm($id)
	{
		$post = $this->findPost($id);
		$latest = PostModeration::query()->where('post_id', $post->id)->whereIn('source', ['ai', 'human'])->orderByDesc('id')->first();
		
		return view('admin.ai.moderation.reject', ['post' => $post, 'latest' => $latest]);
	}
	
	public function approve($id, ModerationService $service)
	{
		$post = $this->findPost($id);
		$service->decideByAdmin($post, ModerationStatus::APPROVED, auth()->user());
		
		notification(trans('ai.queue_approved', ['title' => e(str($post->title)->limit(40))]), 'success');
		
		return redirect()->back();
	}
	
	public function reject($id, Request $request, ModerationService $service)
	{
		$post = $this->findPost($id);
		
		$data = $request->validate([
			'categories'   => ['required', 'array', 'min:1'],
			'categories.*' => ['string', Rule::in(array_keys(Policy::CATEGORIES))],
			'reason'       => ['nullable', 'string', 'max:300'],
		], [], [
			'categories' => trans('ai.queue_categories'),
			'reason'     => trans('ai.queue_reason'),
		]);
		
		$row = $service->decideByAdmin($post, ModerationStatus::REJECTED, auth()->user(), $data['categories'], $data['reason'] ?? null);
		
		$title = e(str($post->title)->limit(40)); // the toast renders HTML
		empty($row->notified_at)
			? notification(trans('ai.queue_rejected_not_notified', ['title' => $title]), 'warning')
			: notification(trans('ai.queue_rejected', ['title' => $title]), 'success');
		
		return redirect()->to(urlGen()->adminUrl('moderation'));
	}
	
	public function rerun($id, ModerationService $service)
	{
		$post = $this->findPost($id);
		
		if ($service->rerun($post)) {
			notification(trans('ai.queue_rerun_queued'), 'info');
		} else {
			notification(trans('ai.queue_rerun_unavailable'), 'warning');
		}
		
		return redirect()->back();
	}
	
	private function findPost($id): Post
	{
		$post = Post::query()->withoutGlobalScopes()->with('user')->find((int)$id);
		abort_if(empty($post), 404);
		
		return $post;
	}
}
