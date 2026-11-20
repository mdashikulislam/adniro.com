{{-- AI review queue: decisions on one listing (adniro:ai) --}}
@extends('admin.layouts.master')

@section('header')
	<div class="row page-titles">
		<div class="col-12 align-self-center">
			<h2 class="mb-0">{{ trans('ai.queue_history_title') }}</h2>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">
						<a href="{{ urlGen()->post($post) }}?preview=1" target="_blank">{{ $post->title }}</a>
						<small class="text-muted">#{{ $post->id }}</small>
						@include('admin.ai.moderation._status', ['status' => $post->moderation_status])
					</h4>
					<a href="{{ urlGen()->adminUrl('moderation') }}" class="btn btn-sm btn-light mb-3">{{ trans('ai.queue_back') }}</a>
					
					@if ($rows->isEmpty())
						<div class="alert alert-light mb-0">{{ trans('ai.queue_no_decision') }}</div>
					@else
						<div class="table-responsive">
							<table class="table table-sm align-middle">
								<thead>
								<tr>
									<th>{{ trans('ai.history_when') }}</th>
									<th>{{ trans('ai.history_by') }}</th>
									<th>{{ trans('ai.history_decided') }}</th>
									<th>{{ trans('ai.history_details') }}</th>
								</tr>
								</thead>
								<tbody>
								@foreach ($rows as $row)
									@php
										$labels = collect((array)($row->categories ?? []))
											->filter(fn ($code) => is_string($code) && \App\Services\Ai\Moderation\Policy::severity($code) !== null)
											->map(fn ($code) => \App\Services\Ai\Moderation\Policy::label($code));
									@endphp
									<tr>
										<td class="small text-nowrap">{{ $row->created_at?->format('Y-m-d H:i') }}<br><span class="text-muted">{{ $row->trigger }} · {{ $row->mode }}</span></td>
										<td class="small">
											@if ($row->source === 'human')
												{{ $row->reviewer?->name ?? trans('ai.history_admin') }}
											@elseif ($row->source === 'ai')
												{{ trans('ai.history_ai') }}<br><span class="text-muted">{{ $row->model }}</span>
											@else
												{{ trans('ai.history_system') }}
											@endif
										</td>
										<td>
											@if ($row->decided_status)
												@include('admin.ai.moderation._status', ['status' => $row->decided_status])
											@endif
											@if ($row->applied_status)
												<div class="small text-muted">{{ trans('ai.history_applied') }}</div>
											@endif
											@if ($row->notified_at)
												<div class="small text-muted">{{ trans('ai.history_emailed') }}</div>
											@endif
										</td>
										<td class="small">
											@if ($labels->isNotEmpty())<div><strong>{{ $labels->implode(', ') }}</strong></div>@endif
											<div>{{ trans('ai.rule_' . ($row->rule ?: 'none')) }}@if ($row->confidence) · {{ trans('ai.queue_confidence') }}: {{ $row->confidence }}@endif</div>
											@if ($row->internal_reason)<div class="text-muted">{{ trans('ai.queue_note') }}: {{ $row->internal_reason }}</div>@endif
											@if ($row->user_reason)<div class="text-muted">{{ trans('ai.queue_user_reason') }}: {{ $row->user_reason }}</div>@endif
											@if ($row->error)<div class="text-danger">{{ $row->error }}</div>@endif
											@if ($row->usageLog)<div class="text-muted">{{ $row->usageLog->input_tokens }} / {{ $row->usageLog->output_tokens }} tokens · {{ $row->usageLog->latency_ms }} ms</div>@endif
										</td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</div>
			</div>
		</div>
	</div>
@endsection
