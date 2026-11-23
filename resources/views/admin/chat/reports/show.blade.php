{{-- Admin > Users > Chat reports: one report and the conversation it is about (adniro) --}}
@extends('admin.layouts.master')

@php
	$memberUrl = fn (int $id) => urlGen()->adminUrl('users/' . $id . '/edit');
	$reporterId = (int)$report->reporter_id;
	$names = [
		$reporterId => $report->reporter?->name ?? trans('chat.deleted_member'),
		$reportedId => $reported?->name ?? trans('chat.deleted_member'),
	];
@endphp

@section('header')
	<div class="row page-titles">
		<div class="col-12 align-self-center">
			<h2 class="mb-0">{{ trans('chat_admin.report_title', ['id' => $report->id]) }}</h2>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-lg-4">
			<div class="card">
				<div class="card-body">
					<a href="{{ urlGen()->adminUrl('chat-reports') }}" class="btn btn-sm btn-light mb-3">{{ trans('chat_admin.back_to_reports') }}</a>
					
					<dl class="mb-0">
						<dt>{{ trans('chat_admin.col_status') }}</dt>
						<dd>
							<span class="badge {{ $report->isOpen() ? 'bg-warning text-dark' : 'bg-success' }}">{{ trans('chat_admin.status_' . $report->status) }}</span>
							@if (!$report->isOpen() && !empty($report->closed_at))
								<div class="small text-muted mt-1">{{ trans('chat_admin.closed_by', ['name' => $report->closer?->name ?? '#' . $report->closed_by, 'when' => $report->closed_at->diffForHumans()]) }}</div>
							@endif
						</dd>
						
						<dt>{{ trans('chat_admin.col_reason') }}</dt>
						<dd>{{ trans('chat.js.reason_' . $report->reason) }}</dd>
						
						@if (!empty($report->note))
							<dt>{{ trans('chat_admin.col_note') }}</dt>
							<dd style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $report->note }}</dd>
						@endif
						
						<dt>{{ trans('chat_admin.col_reported') }}</dt>
						<dd>
							{{ $names[$reportedId] }} <small class="text-muted">#{{ $reportedId }}</small>
							@if (!empty($reported))
								<div><a href="{{ $memberUrl($reportedId) }}">{{ trans('chat_admin.open_member') }}</a></div>
							@endif
							@if ($otherReports > 0)
								<div class="text-danger small mt-1">{{ trans_choice('chat_admin.other_reports', $otherReports, ['count' => $otherReports]) }}</div>
							@endif
						</dd>
						
						<dt>{{ trans('chat_admin.col_reporter') }}</dt>
						<dd>
							{{ $names[$reporterId] }} <small class="text-muted">#{{ $reporterId }}</small>
							@if (!empty($report->reporter))
								<div><a href="{{ $memberUrl($reporterId) }}">{{ trans('chat_admin.open_member') }}</a></div>
							@endif
						</dd>
						
						<dt>{{ trans('chat_admin.col_when') }}</dt>
						<dd class="mb-0">{{ $report->created_at }}</dd>
					</dl>
					
					<hr>
					<form method="post" action="{{ urlGen()->adminUrl('chat-reports/' . $report->id . '/' . ($report->isOpen() ? 'close' : 'reopen')) }}">
						@csrf
						@if ($report->isOpen())
							<button type="submit" class="btn btn-success">{{ trans('chat_admin.close_report') }}</button>
							<p class="small text-muted mt-2 mb-0">{{ trans('chat_admin.close_help') }}</p>
						@else
							<button type="submit" class="btn btn-light">{{ trans('chat_admin.reopen_report') }}</button>
						@endif
					</form>
				</div>
			</div>
		</div>
		
		<div class="col-lg-8">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">{{ trans('chat_admin.conversation') }}</h4>
					<p class="small text-muted">
						{{ trans_choice('chat_admin.messages_shown', $messages->count(), ['count' => $messages->count(), 'total' => $totalCount]) }}
					</p>
					
					@forelse ($messages as $message)
						@php
							$senderId = (int)$message->user_id;
							$isReported = $senderId === $reportedId;
						@endphp
						<div class="border rounded p-2 mb-2 {{ $isReported ? 'border-danger' : '' }}">
							<div class="d-flex justify-content-between small text-muted mb-1">
								<strong class="{{ $isReported ? 'text-danger' : 'text-body' }}">{{ $names[$senderId] ?? '#' . $senderId }}</strong>
								<span>{{ $message->created_at }}</span>
							</div>
							@if (!empty($message->post))
								<div class="small text-muted mb-1">{{ trans('chat_admin.about_listing', ['title' => $message->post->title]) }}</div>
							@endif
							@if ($message->isImage())
								<img src="{{ urlGen()->adminUrl('chat-reports/' . $report->id . '/photos/' . $message->id) }}" alt="{{ trans('chat.js.photo') }}"
								     loading="lazy" style="max-width: 220px; max-height: 220px; border-radius: 8px;">
							@elseif ($message->isOffer() || $message->isOfferAnswer())
								<div><strong>{{ trans($message->isOffer() ? 'chat_admin.offer_line' : 'chat_admin.offer_answer_line', [
									'amount' => \App\Services\Chat\ChatOffers::amountOf($message),
									'status' => trans('chat_admin.offer_status_' . $message->offer_status),
								]) }}</strong></div>
							@else
								<div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $message->body }}</div>
							@endif
							@if ($message->is_auto)
								<div class="small text-muted mt-1">{{ trans('chat_admin.automatic_reply') }}</div>
							@endif
							@if ($message->isUnsent())
								<div class="small text-warning mt-1">{{ trans('chat_admin.taken_back', ['when' => $message->unsent_at]) }}</div>
							@endif
						</div>
					@empty
						<div class="alert alert-light mb-0">{{ trans('chat_admin.no_messages') }}</div>
					@endforelse
				</div>
			</div>
		</div>
	</div>
@endsection
