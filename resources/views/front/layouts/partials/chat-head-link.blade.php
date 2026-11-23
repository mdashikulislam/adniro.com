{{--
	adniro: the chat's place in the header for signed-in members: an icon with the live unread badge
	(public/assets/js/app/chat-live.js keeps it up to date; styles in public/assets/css/mobile-nav.css).
	$variant "nav": in the menu bar, from 1200px. "compact": beside the account button, 768 to 1199px.
	Phones have the Chats tab of the app bar instead.
--}}
@auth
	@php
		$chatHeadBadge = \App\Services\Chat\ChatUnread::badgeHtml('adn-head-chat-badge');
	@endphp
	@if (($variant ?? 'nav') === 'nav')
		<li class="nav-item adn-head-chat-item d-none d-xl-flex">
			<a href="{{ route('chat.index') }}" class="adn-head-chat" aria-label="{{ trans('chat.chats') }}" title="{{ trans('chat.chats') }}">
				<i class="bi bi-chat-dots" aria-hidden="true"></i>{!! $chatHeadBadge !!}
			</a>
		</li>
	@else
		<a href="{{ route('chat.index') }}" class="adn-head-chat is-compact d-none d-md-inline-flex d-xl-none" aria-label="{{ trans('chat.chats') }}">
			<i class="bi bi-chat-dots" aria-hidden="true"></i>{!! $chatHeadBadge !!}
		</a>
	@endif
@endauth
