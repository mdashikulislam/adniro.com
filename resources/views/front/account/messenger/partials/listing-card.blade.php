{{-- adniro: the listing the conversation is about, at the top of the conversation --}}
@php
	$chatPostId = (int)data_get($thread, 'post_id', data_get($thread, 'post.id'));
	$chatPost = $chatPostId > 0
		? \App\Models\Post::query()->withoutGlobalScopes()->with('picture')->find($chatPostId)
		: null;
@endphp
@if ($chatPost)
	@php
		$chatPostUrl = urlGen()->post($chatPost);
		$chatPicture = data_get($chatPost, 'picture.file_url_small');
	@endphp
	<a href="{{ $chatPostUrl }}" class="chat-listing-card d-flex align-items-center gap-3 mb-3 text-decoration-none">
		@if (!empty($chatPicture))
			<img src="{{ $chatPicture }}" alt="" class="chat-listing-thumb" width="64" height="48" loading="lazy">
		@else
			<span class="chat-listing-thumb chat-listing-thumb-empty"><i class="bi bi-image"></i></span>
		@endif
		<span class="flex-grow-1 min-w-0">
			<span class="chat-listing-title d-block text-truncate">{{ $chatPost->title }}</span>
			@if (!empty($chatPost->price_formatted))
				<span class="chat-listing-price d-block">{!! $chatPost->price_formatted !!}</span>
			@endif
		</span>
		<span class="chat-listing-link small text-nowrap">{{ t('view_listing') }} <i class="bi bi-arrow-right"></i></span>
	</a>
@endif
