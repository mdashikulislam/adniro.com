{{-- adniro:ai - after posting, while moderation is enforced --}}
@if (app(\App\Services\Ai\Moderation\ModerationService::class)->isEnforcing())
	<p class="listing-finish-text text-muted small mb-0"><i class="bi bi-shield-check"></i> {{ trans('ai.finish_checking') }}</p>
@endif
