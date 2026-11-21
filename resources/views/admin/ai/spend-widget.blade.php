{{-- adniro:ai - AI spend and activity, for super admins --}}
@php
	$aiSpend = userHasSuperAdminPermissions() ? app(\App\Services\Ai\AiSpend::class)->summary() : null;
@endphp
@if (!empty($aiSpend))
	<div class="col-lg-6 col-md-12">
		<div class="card rounded-0 border-0 border-top border-primary shadow-sm" id="aiSpendWidget">
			<div class="card-body">
				<div class="d-md-flex">
					<h4 class="card-title fw-bold">
						<span class="lstick d-inline-block align-middle"></span>{{ trans('ai.spend_title') }}
					</h4>
					<div class="ms-auto">
						<a href="{{ urlGen()->adminUrl('settings/find/ai') }}" class="btn btn-sm btn-primary rounded shadow">{{ trans('ai.spend_settings') }}</a>
					</div>
				</div>
				
				@if (!$aiSpend['enabled'])
					<p class="text-muted small mb-2">{{ trans('ai.spend_off') }}</p>
				@endif
				
				<div class="row text-center g-2 my-2">
					<div class="col-4">
						<div class="fs-4 fw-bold">${{ number_format($aiSpend['today_cost'], 2) }}</div>
						<div class="small text-muted">{{ trans('ai.spend_today') }}</div>
					</div>
					<div class="col-4">
						<div class="fs-4 fw-bold">{{ $aiSpend['today_calls'] }}</div>
						<div class="small text-muted">{{ trans('ai.spend_calls') }}@if ($aiSpend['today_failed'] > 0) · {{ trans('ai.spend_failed', ['count' => $aiSpend['today_failed']]) }}@endif</div>
					</div>
					<div class="col-4">
						<div class="fs-4 fw-bold">${{ number_format($aiSpend['month_cost'], 2) }}</div>
						<div class="small text-muted">{{ trans('ai.spend_month') }}</div>
					</div>
				</div>
				
				@if (!is_null($aiSpend['budget_used']))
					<div class="small mb-1">{{ trans('ai.spend_budget', ['used' => $aiSpend['budget_used'], 'budget' => number_format($aiSpend['budget'], 2)]) }}</div>
					<div class="progress mb-3" style="height: 6px;" role="progressbar" aria-valuenow="{{ $aiSpend['budget_used'] }}" aria-valuemin="0" aria-valuemax="100">
						<div class="progress-bar {{ $aiSpend['budget_used'] >= 90 ? 'bg-danger' : ($aiSpend['budget_used'] >= 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $aiSpend['budget_used'] }}%"></div>
					</div>
				@endif
				
				<table class="table table-sm mb-3">
					<tbody>
						@foreach (['moderation', 'listing_assistant', 'test'] as $feature)
							<tr>
								<td>{{ trans('ai.spend_feature_' . $feature) }}</td>
								<td class="text-end">{{ $aiSpend['features'][$feature]['calls'] ?? 0 }} {{ trans('ai.spend_calls_short') }}</td>
								<td class="text-end">${{ number_format($aiSpend['features'][$feature]['cost'] ?? 0, 4) }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
				
				@php
					$maxDay = max(0.0001, max(array_column($aiSpend['days'], 'cost')));
				@endphp
				<div class="d-flex align-items-end gap-1 mb-1" style="height: 48px;" aria-label="{{ trans('ai.spend_week') }}">
					@foreach ($aiSpend['days'] as $day)
						<div class="flex-fill bg-primary bg-opacity-50 rounded-top" style="height: {{ max(2, round($day['cost'] / $maxDay * 48)) }}px;"
						     title="{{ $day['day'] }}: ${{ number_format($day['cost'], 4) }}, {{ $day['calls'] }} {{ trans('ai.spend_calls_short') }}"></div>
					@endforeach
				</div>
				<div class="small text-muted mb-3">{{ trans('ai.spend_week') }}</div>
				
				<div class="d-flex flex-wrap gap-3 small">
					<a href="{{ urlGen()->adminUrl('moderation') }}">{{ trans('ai.spend_needs_review', ['count' => $aiSpend['needs_review']]) }}</a>
					<span>{{ trans('ai.spend_rejected_today', ['count' => $aiSpend['rejected_today']]) }}</span>
					<span>{{ trans('ai.spend_drafts_today', ['count' => $aiSpend['drafts_today']]) }}</span>
				</div>
			</div>
		</div>
	</div>
@endif
