{{--
	adniro: live chat between a buyer and a seller: the inbox and the open conversation, in one page
	(public/assets/js/app/chat.js fills it from the data below; public/assets/css/chat.css).
	Phones: the inbox is a normal page, an open conversation is full screen. Design: replica/design/components.md.
--}}
@extends('front.layouts.master')

@php
	$chatIsOpen = !empty($chatState['open']) || !empty($chatState['draft']);
@endphp

@section('body_class', ' adn-chat-page' . ($chatIsOpen ? ' is-chat-open' : ''))

@push('after_styles_stack')
	<link href="{{ url('assets/css/chat.css') }}?v={{ @filemtime(public_path('assets/css/chat.css')) }}" rel="stylesheet">
@endpush

@section('content')
	<div class="main-container adn-chat-main">
		<div class="container">
			<div class="adn-chat" id="adnChat" data-unblock-label="{{ trans('chat.unblock') }}">
				{{-- Inbox --}}
				<nav class="adn-chat-inbox" aria-label="{{ trans('chat.chats') }}">
					<div class="adn-chat-inbox-head">
						<h1 class="adn-chat-title">{{ trans('chat.chats') }}</h1>
						<div class="adn-chat-filters" role="group" aria-label="{{ trans('chat.chats') }}">
							<button type="button" class="adn-chat-filter" data-chat-filter="">{{ trans('chat.filter_all') }}</button>
							<button type="button" class="adn-chat-filter" data-chat-filter="unread">
								{{ trans('chat.filter_unread') }} <span class="adn-chat-filter-count" data-adn-chat-badge @if (empty($chatState['unread_total'])) hidden @endif>{{ $chatState['unread_total'] > 99 ? '99+' : $chatState['unread_total'] }}</span>
							</button>
						</div>
					</div>
					<div class="adn-chat-list" data-chat-list aria-live="polite"></div>
				</nav>

				{{-- Conversation --}}
				<section class="adn-chat-conv" data-chat-conv aria-label="{{ trans('chat.conversation_label') }}">
					<div class="adn-chat-pick" data-chat-pick>
						<span class="adn-chat-empty-icon" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
						<strong>{{ trans('chat.js.pick_title') }}</strong>
						<span>{{ trans('chat.js.pick_text') }}</span>
					</div>

					<div class="adn-chat-open" data-chat-open hidden>
						<header class="adn-chat-head">
							<a href="{{ route('chat.index') }}" class="adn-chat-back" data-chat-back aria-label="{{ trans('chat.back_to_chats') }}">
								<i class="bi bi-arrow-left" aria-hidden="true"></i>
							</a>
							<span class="adn-chat-avatar adn-chat-avatar-sm" data-chat-avatar aria-hidden="true"></span>
							<div class="adn-chat-head-text">
								<strong class="adn-chat-head-name" data-chat-name></strong>
								<span class="adn-chat-head-status" data-chat-status aria-live="polite"></span>
							</div>
							<div class="dropdown">
								<button type="button" class="adn-chat-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ trans('chat.more_actions') }}" data-chat-menu-btn>
									<i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
								</button>
								<ul class="dropdown-menu dropdown-menu-end adn-chat-menu">
									<li><a class="dropdown-item" href="#" data-chat-view-listing><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> {{ trans('chat.view_listing') }}</a></li>
									<li><button type="button" class="dropdown-item" data-chat-block><i class="bi bi-slash-circle" aria-hidden="true"></i> <span data-chat-block-label>{{ trans('chat.block') }}</span></button></li>
									<li><button type="button" class="dropdown-item text-danger" data-chat-report><i class="bi bi-flag" aria-hidden="true"></i> {{ trans('chat.report') }}</button></li>
								</ul>
							</div>
						</header>

						<div class="adn-chat-offline" data-chat-offline hidden>{{ trans('chat.js.reconnecting') }}</div>

						<a class="adn-chat-listing" data-chat-listing>
							<span class="adn-chat-listing-photo" data-chat-listing-photo aria-hidden="true"><i class="bi bi-image"></i></span>
							<span class="adn-chat-listing-text">
								<strong data-chat-listing-title></strong>
								<span data-chat-listing-price></span>
							</span>
							<i class="bi bi-chevron-right adn-chat-listing-go" aria-hidden="true"></i>
						</a>

						<div class="adn-chat-log-wrap">
							<div class="adn-chat-log" data-chat-log role="log" aria-live="polite" tabindex="0">
								<div class="adn-chat-older" data-chat-older hidden><span class="spinner-border spinner-border-sm" aria-hidden="true"></span></div>
								@include('front.chat.partials.safety')
								<div data-chat-messages></div>
								<div class="adn-chat-typing" data-chat-typing hidden aria-hidden="true"><span></span><span></span><span></span></div>
							</div>
							<button type="button" class="adn-chat-new" data-chat-new hidden>
								<i class="bi bi-arrow-down" aria-hidden="true"></i> {{ trans('chat.js.new_messages') }}
							</button>
						</div>

						<div class="adn-chat-quick" data-chat-quick role="group" aria-label="{{ trans('chat.quick_replies_label') }}" hidden></div>

						<div class="adn-chat-photo-preview" data-chat-photo-preview hidden>
							<img alt="" data-chat-photo-img width="64" height="64">
							<button type="button" class="adn-chat-photo-remove" data-chat-photo-remove aria-label="{{ trans('chat.remove_photo') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
						</div>
						<p class="adn-chat-error" data-chat-error role="alert" hidden></p>

						<form class="adn-chat-composer" data-chat-composer novalidate>
							<input type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" data-chat-photo-input hidden>
							<button type="button" class="adn-chat-icon-btn" data-chat-photo-btn aria-label="{{ trans('chat.add_photo') }}">
								<i class="bi bi-image" aria-hidden="true"></i>
							</button>
							<label class="visually-hidden" for="adnChatInput">{{ trans('chat.message_label') }}</label>
							<div class="adn-chat-input-wrap">
								<textarea id="adnChatInput" class="adn-chat-input" rows="1" maxlength="{{ $chatState['limits']['body_max'] }}" placeholder="{{ trans('chat.message_placeholder') }}" data-chat-input autocomplete="off"></textarea>
								<span class="adn-chat-count" data-chat-count hidden></span>
							</div>
							<button type="submit" class="adn-chat-send" data-chat-send disabled aria-label="{{ trans('chat.send') }}">
								<i class="bi bi-send-fill" aria-hidden="true"></i>
							</button>
						</form>
						<p class="adn-chat-blocked" data-chat-blocked hidden></p>
					</div>
				</section>
			</div>
		</div>
	</div>

	@include('front.chat.partials.report-dialog')

	<script type="application/json" id="adnChatState">{!! json_encode($chatState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endsection

@push('after_scripts_stack')
	@foreach (['chat-kit', 'chat-inbox', 'chat-log', 'chat-safety', 'chat-photo', 'chat'] as $chatScript)
		<script src="{{ url('assets/js/app/' . $chatScript . '.js') }}?v={{ @filemtime(public_path('assets/js/app/' . $chatScript . '.js')) }}" defer></script>
	@endforeach
@endpush
