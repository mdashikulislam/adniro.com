{{-- adniro:ai - AI post limit of one user --}}
@if (\App\Http\Controllers\Web\Admin\AiPostLimitController::allows(auth()->user()))
	<a href="{{ urlGen()->adminUrl('users/' . $entry->getKey() . '/ai-post-limit') }}" class="btn btn-xs btn-light" title="{{ trans('ai.user_limit_button') }}">
		<i class="bi bi-stars"></i> {{ trans('ai.user_limit_button') }}
	</a>
@endif
