{{-- adniro:ai - moderation status of a hidden listing, shown to its owner and staff only --}}
@php
	$moderationStatus = data_get($post, 'moderation_status');
	$isHiddenByModeration = !\App\Services\Ai\Moderation\ModerationStatus::isPublic($moderationStatus);
	$canSeeModeration = $isHiddenByModeration && \App\Services\Ai\Moderation\ModerationVisibility::canSee($post);
	
	$moderationDecision = null;
	if ($canSeeModeration && $moderationStatus === \App\Services\Ai\Moderation\ModerationStatus::REJECTED) {
		$moderationDecision = \App\Models\PostModeration::query()
			->where('post_id', data_get($post, 'id'))
			->where('applied_status', \App\Services\Ai\Moderation\ModerationStatus::REJECTED)
			->orderByDesc('id')
			->first();
	}
@endphp
@if ($canSeeModeration)
	<div class="container">
		<div class="row">
			<div class="col-12">
				@if ($moderationStatus === \App\Services\Ai\Moderation\ModerationStatus::REJECTED)
					<div class="alert alert-danger" role="alert">
						<p class="fw-bold mb-1"><i class="bi bi-x-octagon"></i> {{ trans('ai.owner_rejected') }}</p>
						@php
							$moderationLabels = collect((array)($moderationDecision?->categories ?? []))
								->filter(fn ($code) => is_string($code) && \App\Services\Ai\Moderation\Policy::severity($code) !== null)
								->map(fn ($code) => \App\Services\Ai\Moderation\Policy::label($code));
						@endphp
						@if ($moderationLabels->isNotEmpty())
							<p class="mb-1"><strong>{{ trans('ai.mail_not_approved_categories') }}</strong> {{ $moderationLabels->implode(', ') }}</p>
						@endif
						@if (!empty($moderationDecision?->user_reason))
							<p class="mb-1">{{ $moderationDecision->user_reason }}</p>
						@endif
						<p class="mb-2">{{ trans('ai.owner_rejected_fix') }}</p>
						<a href="{{ urlGen()->editPost($post) }}" class="btn btn-sm btn-danger"><i class="bi bi-pencil-square"></i> {{ trans('ai.owner_edit') }}</a>
					</div>
				@elseif ($moderationStatus === \App\Services\Ai\Moderation\ModerationStatus::NEEDS_REVIEW)
					<div class="alert alert-warning" role="alert">
						<i class="bi bi-hourglass-split"></i> {{ trans('ai.owner_needs_review') }}
					</div>
				@else
					<div class="alert alert-info" role="alert">
						<i class="bi bi-shield-check"></i> {{ trans('ai.owner_pending') }}
					</div>
				@endif
			</div>
		</div>
	</div>
@endif
