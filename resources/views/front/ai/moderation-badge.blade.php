{{-- adniro:ai - moderation status badge in My Listings --}}
@php
	$moderationStatus = data_get($post, 'moderation_status');
	$moderationBadge = match ($moderationStatus) {
		\App\Services\Ai\Moderation\ModerationStatus::PENDING => ['text-bg-info', trans('ai.badge_pending')],
		\App\Services\Ai\Moderation\ModerationStatus::NEEDS_REVIEW => ['text-bg-warning', trans('ai.badge_needs_review')],
		\App\Services\Ai\Moderation\ModerationStatus::REJECTED => ['text-bg-danger', trans('ai.badge_rejected')],
		default => null,
	};
@endphp
@if (!empty($moderationBadge))
	<span class="badge rounded-pill {{ $moderationBadge[0] }}">{{ $moderationBadge[1] }}</span>
@endif
