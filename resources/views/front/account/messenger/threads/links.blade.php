@php
	$apiResult ??= [];
	$from = (int)data_get($apiResult, 'meta.from', 0);
	$to = (int)data_get($apiResult, 'meta.to', 0);
	$totalEntries = (int)data_get($apiResult, 'meta.total', 0);
@endphp
@if ($totalEntries > 0)
	<span class="text-muted count-message">
		@php
			$linksMeta = t('pagination_meta_simple', [
				'from'  => '<span class="fw-bold">' . $from . '</span>',
				'to'    => '<span class="fw-bold">' . $to . '</span>',
				'total' => '<span class="fw-bold">' . $totalEntries . '</span>',
			]);
		@endphp
		{!! $linksMeta !!}
	</span>
	
	@include('front.account.messenger.threads.pagination')
@endif
