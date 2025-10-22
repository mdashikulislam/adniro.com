@if (!empty($threads) && $totalThreads > 0)
	@foreach($threads as $thread)
		@php
			$isLastThread = $loop->last;
		@endphp
		@include('front.account.messenger.threads.thread', [
			'thread'       => $thread,
			'isLastThread' => $isLastThread,
		])
	@endforeach
@else
	@include('front.account.messenger.threads.no-threads')
@endif
