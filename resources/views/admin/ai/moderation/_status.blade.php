{{-- adniro:ai --}}
@php
	$statusClass = match ($status) {
		'approved' => 'bg-success',
		'pending' => 'bg-info',
		'needs_review' => 'bg-warning text-dark',
		'rejected' => 'bg-danger',
		default => 'bg-secondary',
	};
@endphp
<span class="badge {{ $statusClass }}">{{ trans('ai.status_' . ($status ?: 'none')) }}</span>
