{{-- Admin > Listings > AI review queue (adniro:ai) --}}
@extends('admin.layouts.master')

@section('header')
	<div class="row page-titles">
		<div class="col-md-6 col-12 align-self-center">
			<h2 class="mb-0">{{ trans('ai.queue_title') }}</h2>
		</div>
		<div class="col-md-6 col-12 align-self-center d-none d-md-flex justify-content-end">
			<ol class="breadcrumb mb-0 p-0 bg-transparent">
				<li class="breadcrumb-item"><a href="{{ urlGen()->adminUrl() }}">{{ trans('admin.dashboard') }}</a></li>
				<li class="breadcrumb-item active d-flex align-items-center">{{ trans('ai.queue_title') }}</li>
			</ol>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<p class="mb-2">{{ trans('ai.queue_help') }}</p>
					<p class="mb-0 text-muted small">{{ trans('ai.queue_mode', ['mode' => trans('ai.setting_moderation_mode_' . $mode)]) }}</p>
				</div>
			</div>
			
			<div class="card">
				<div class="card-body">
					<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
						<ul class="nav nav-pills">
							@foreach (\App\Http\Controllers\Web\Admin\ModerationQueueController::FILTERS as $item)
								<li class="nav-item">
									<a class="nav-link {{ $filter === $item ? 'active' : '' }}"
									   href="{{ urlGen()->adminUrl('moderation') . '?status=' . $item }}">
										{{ trans('ai.queue_filter_' . $item) }}
										@if (isset($counts[$item]))
											<span class="badge bg-light text-dark ms-1">{{ $counts[$item] }}</span>
										@endif
									</a>
								</li>
							@endforeach
						</ul>
						<form method="get" action="{{ urlGen()->adminUrl('moderation') }}" class="d-flex gap-2">
							<input type="hidden" name="status" value="{{ $filter }}">
							<input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="{{ trans('ai.queue_search') }}">
							<button class="btn btn-sm btn-primary" type="submit">{{ trans('ai.queue_search_button') }}</button>
						</form>
					</div>
					
					@if ($posts->isEmpty())
						<div class="alert alert-light mb-0">{{ trans('ai.queue_empty') }}</div>
					@else
						<div class="table-responsive">
							<table class="table table-striped align-middle">
								<thead>
								<tr>
									<th>{{ trans('ai.queue_col_listing') }}</th>
									<th>{{ trans('ai.queue_col_status') }}</th>
									<th>{{ trans('ai.queue_col_ai') }}</th>
									<th>{{ trans('ai.queue_col_waiting') }}</th>
									<th class="text-end">{{ trans('ai.queue_col_actions') }}</th>
								</tr>
								</thead>
								<tbody>
								@foreach ($posts as $post)
									@php
										$decision = $decisions->get($post->id);
										$labels = collect((array)($decision?->categories ?? []))
											->filter(fn ($code) => is_string($code) && \App\Services\Ai\Moderation\Policy::severity($code) !== null)
											->map(fn ($code) => \App\Services\Ai\Moderation\Policy::label($code));
										$baseUrl = urlGen()->adminUrl('moderation/' . $post->id);
									@endphp
									<tr>
										<td style="min-width: 220px">
											<a href="{{ urlGen()->post($post) }}?preview=1" target="_blank" class="fw-bold">{{ str($post->title)->limit(60) }}</a>
											<div class="small text-muted">
												#{{ $post->id }}
												· {{ $post->user?->name ?? $post->contact_name ?? trans('ai.queue_guest') }}
												· <a href="{{ urlGen()->adminUrl('posts/' . $post->id . '/edit') }}">{{ trans('ai.queue_edit_in_admin') }}</a>
											</div>
										</td>
										<td>
											@include('admin.ai.moderation._status', ['status' => $post->moderation_status])
											@if ($decision?->mode === 'shadow' && $decision?->decided_status)
												<div class="small text-muted mt-1">{{ trans('ai.queue_would_be') }} @include('admin.ai.moderation._status', ['status' => $decision->decided_status])</div>
											@endif
										</td>
										<td style="min-width: 260px">
											@if ($decision)
												@if ($labels->isNotEmpty())
													<div><strong>{{ $labels->implode(', ') }}</strong></div>
												@endif
												<div class="small">
													@if ($decision->confidence)
														{{ trans('ai.queue_confidence') }}: {{ $decision->confidence }} ·
													@endif
													{{ trans('ai.rule_' . ($decision->rule ?: 'none')) }}
													@if ($decision->model)
														· <span class="text-muted">{{ $decision->model }}</span>
													@endif
												</div>
												@if ($decision->internal_reason)
													<div class="small text-muted">{{ trans('ai.queue_note') }}: {{ str($decision->internal_reason)->limit(160) }}</div>
												@endif
												@if ($decision->user_reason)
													<div class="small text-muted">{{ trans('ai.queue_user_reason') }}: {{ str($decision->user_reason)->limit(160) }}</div>
												@endif
												@if ($decision->error)
													<div class="small text-danger">{{ str($decision->error)->limit(120) }}</div>
												@endif
											@else
												<span class="text-muted small">{{ trans('ai.queue_no_decision') }}</span>
											@endif
										</td>
										<td class="small text-nowrap">{{ $post->updated_at?->diffForHumans() }}</td>
										<td class="text-end text-nowrap">
											<form method="post" action="{{ $baseUrl }}/approve" class="d-inline">
												@csrf
												<button type="submit" class="btn btn-sm btn-success">{{ trans('ai.queue_approve') }}</button>
											</form>
											<a href="{{ $baseUrl }}/reject" class="btn btn-sm btn-danger">{{ trans('ai.queue_reject') }}</a>
											<form method="post" action="{{ $baseUrl }}/rerun" class="d-inline">
												@csrf
												<button type="submit" class="btn btn-sm btn-outline-secondary">{{ trans('ai.queue_rerun') }}</button>
											</form>
											<a href="{{ $baseUrl }}/history" class="btn btn-sm btn-outline-primary">{{ trans('ai.queue_history') }}</a>
										</td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>
						{{ $posts->links() }}
					@endif
				</div>
			</div>
		</div>
	</div>
@endsection
