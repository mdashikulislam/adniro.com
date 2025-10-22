@php
	$defaultMessage = t('No message received');
	$messages = [
		'unread'    => t('No new thread or with new messages'),
		'started'   => t('No thread started by you'),
		'important' => t('No message marked as important'),
	];
	$filter = request()->query('filter');
	$filter = (!empty($filter) && is_string($filter)) ? $filter : '-';
	$emptyMessage = $messages[$filter] ?? $defaultMessage;
@endphp
<div class="row my-5">
	<div class="col-12 text-center">
		{{ $emptyMessage }}
	</div>
</div>
