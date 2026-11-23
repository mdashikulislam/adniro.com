{{-- Admin > Users > Chat reports (adniro): the conversations members reported --}}
@extends('admin.layouts.master')

@section('header')
	<div class="row page-titles">
		<div class="col-md-6 col-12 align-self-center">
			<h2 class="mb-0">{{ trans('chat_admin.reports_title') }}</h2>
		</div>
		<div class="col-md-6 col-12 align-self-center d-none d-md-flex justify-content-end">
			<ol class="breadcrumb mb-0 p-0 bg-transparent">
				<li class="breadcrumb-item"><a href="{{ urlGen()->adminUrl() }}">{{ trans('admin.dashboard') }}</a></li>
				<li class="breadcrumb-item active d-flex align-items-center">{{ trans('chat_admin.reports_title') }}</li>
			</ol>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<p class="mb-0">{{ trans('chat_admin.reports_help') }}</p>
				</div>
			</div>
			
			<div class="card">
				<div class="card-body">
					<ul class="nav nav-pills mb-3">
						@foreach (\App\Models\Chat\ThreadReport::STATUSES as $item)
							<li class="nav-item">
								<a class="nav-link {{ $status === $item ? 'active' : '' }}" href="{{ urlGen()->adminUrl('chat-reports') . '?status=' . $item }}">
									{{ trans('chat_admin.status_' . $item) }}
									<span class="badge bg-light text-dark ms-1">{{ $counts[$item] ?? 0 }}</span>
								</a>
							</li>
						@endforeach
					</ul>
					
					@if ($reports->isEmpty())
						<div class="alert alert-light mb-0">{{ trans('chat_admin.reports_empty_' . $status) }}</div>
					@else
						<div class="table-responsive">
							<table class="table table-striped align-middle">
								<thead>
								<tr>
									<th>#</th>
									<th>{{ trans('chat_admin.col_reason') }}</th>
									<th>{{ trans('chat_admin.col_reported') }}</th>
									<th>{{ trans('chat_admin.col_reporter') }}</th>
									<th>{{ trans('chat_admin.col_when') }}</th>
									<th class="text-end"></th>
								</tr>
								</thead>
								<tbody>
								@foreach ($reports as $report)
									@php
										$reportedId = $report->reportedUserId();
										$reported = $report->thread?->participantFor($reportedId)?->user;
									@endphp
									<tr>
										<td>{{ $report->id }}</td>
										<td>
											<span class="badge {{ $report->reason === 'scam' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ trans('chat.js.reason_' . $report->reason) }}</span>
											@if (!empty($report->note))
												<div class="small text-muted mt-1">{{ str($report->note)->limit(80) }}</div>
											@endif
										</td>
										<td>{{ $reported?->name ?? trans('chat.deleted_member') }} <small class="text-muted">#{{ $reportedId }}</small></td>
										<td>{{ $report->reporter?->name ?? trans('chat.deleted_member') }} <small class="text-muted">#{{ $report->reporter_id }}</small></td>
										<td><span title="{{ $report->created_at }}">{{ $report->created_at?->diffForHumans() }}</span></td>
										<td class="text-end">
											<a href="{{ urlGen()->adminUrl('chat-reports/' . $report->id) }}" class="btn btn-sm btn-primary">{{ trans('chat_admin.open_report') }}</a>
										</td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>
						{{ $reports->links() }}
					@endif
				</div>
			</div>
		</div>
	</div>
@endsection
