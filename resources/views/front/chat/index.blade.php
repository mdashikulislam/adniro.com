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
	@foreach (['chat', 'chat-messages', 'chat-marks', 'chat-offers', 'chat-tidy', 'chat-photos'] as $chatStyle)
		<link href="{{ url('assets/css/' . $chatStyle . '.css') }}?v={{ @filemtime(public_path('assets/css/' . $chatStyle . '.css')) }}" rel="stylesheet">
	@endforeach
@endpush

@section('content')
	<div class="main-container adn-chat-main">
		<div class="container">
			<div class="adn-chat" id="adnChat" data-unblock-label="{{ trans('chat.unblock') }}">
				{{-- Inbox --}}
				<nav class="adn-chat-inbox" aria-label="{{ trans('chat.chats') }}">
					<div class="adn-chat-inbox-head">
						<h1 class="adn-chat-title">{{ trans('chat.chats') }}</h1>
						{{-- Chat settings: the unread email, the sound, browser notifications (chat-settings.js), my tools, privacy --}}
						<div class="dropdown adn-chat-settings">
							<button type="button" class="adn-chat-icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ trans('chat.settings') }}">
								<i class="bi bi-gear" aria-hidden="true"></i>
							</button>
							<div class="dropdown-menu dropdown-menu-end adn-chat-settings-menu">
								<label class="adn-chat-switch">
									<span class="adn-chat-switch-text">
										<strong>{{ trans('chat.emails_label') }}</strong>
										<span>{{ trans('chat.emails_hint') }}</span>
									</span>
									<span class="form-check form-switch m-0">
										<input type="checkbox" class="form-check-input" role="switch" data-chat-emails @checked(!empty($chatState['emails']))>
									</span>
								</label>
								{{-- These two are kept in this browser: a sound or a notification belongs to a device --}}
								<label class="adn-chat-switch">
									<span class="adn-chat-switch-text">
										<strong>{{ trans('chat.sound_label') }}</strong>
										<span>{{ trans('chat.sound_hint') }}</span>
									</span>
									<span class="form-check form-switch m-0">
										<input type="checkbox" class="form-check-input" role="switch" data-chat-sound>
									</span>
								</label>
								<label class="adn-chat-switch" data-chat-desktop-row>
									<span class="adn-chat-switch-text">
										<strong>{{ trans('chat.desktop_label') }}</strong>
										<span>{{ trans('chat.desktop_hint') }}</span>
									</span>
									<span class="form-check form-switch m-0">
										<input type="checkbox" class="form-check-input" role="switch" data-chat-desktop>
									</span>
								</label>
								<button type="button" class="adn-chat-settings-link" data-chat-tools-open>
									<i class="bi bi-lightning-charge" aria-hidden="true"></i> {{ trans('chat.tools_title') }}
								</button>
								<button type="button" class="adn-chat-settings-link" data-chat-privacy-open>
									<i class="bi bi-shield-lock" aria-hidden="true"></i> {{ trans('chat.privacy_title') }}
								</button>
							</div>
						</div>
					</div>
					{{-- Find a chat by the other person's name or by listing (chat-tidy.js) --}}
					<div class="adn-chat-search" role="search">
						<i class="bi bi-search" aria-hidden="true"></i>
						<input type="search" class="adn-chat-search-input" data-chat-search maxlength="60" autocomplete="off" enterkeyhint="search"
						       placeholder="{{ trans('chat.search_placeholder') }}" aria-label="{{ trans('chat.search_label') }}">
						<button type="button" class="adn-chat-search-clear" data-chat-search-clear aria-label="{{ trans('chat.search_clear') }}" hidden>
							<i class="bi bi-x-lg" aria-hidden="true"></i>
						</button>
					</div>
					<div class="adn-chat-filters" role="group" aria-label="{{ trans('chat.filters_label') }}">
						<button type="button" class="adn-chat-filter" data-chat-filter="">{{ trans('chat.filter_all') }}</button>
						<button type="button" class="adn-chat-filter" data-chat-filter="unread">
							{{ trans('chat.filter_unread') }} <span class="adn-chat-filter-count" data-adn-chat-badge @if (empty($chatState['unread_total'])) hidden @endif>{{ $chatState['unread_total'] > 99 ? '99+' : $chatState['unread_total'] }}</span>
						</button>
						<button type="button" class="adn-chat-filter" data-chat-filter="archived">{{ trans('chat.filter_archived') }}</button>
					</div>
					{{-- A reminder while the away message is on: it answers for the member --}}
					<button type="button" class="adn-chat-away-chip" data-chat-away-chip data-chat-tools-open hidden>
						<i class="bi bi-moon-stars" aria-hidden="true"></i> {{ trans('chat.away_on_badge') }}
					</button>
					{{-- Not a live region: the list is drawn again at every change. What is new is said in [data-chat-announce] --}}
					<div class="adn-chat-list" data-chat-list></div>
				</nav>

				{{-- For screen readers: one short sentence when something happens (a new message, a search result) --}}
				<div class="visually-hidden" role="status" aria-live="polite" data-chat-announce></div>

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
								{{-- Focus moves here when a chat is opened on a phone, or one that cannot be written in --}}
								<h2 class="adn-chat-head-name adn-chat-own-dir" data-chat-name tabindex="-1" dir="auto"></h2>
								<span class="adn-chat-head-status" data-chat-status></span>
							</div>
							<div class="dropdown">
								<button type="button" class="adn-chat-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ trans('chat.more_actions') }}" data-chat-menu-btn>
									<i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
								</button>
								<ul class="dropdown-menu dropdown-menu-end adn-chat-menu">
									<li><a class="dropdown-item" href="#" data-chat-view-listing><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> {{ trans('chat.view_listing') }}</a></li>
									<li><button type="button" class="dropdown-item" data-chat-sold><i class="bi bi-bag-check" aria-hidden="true"></i> {{ trans('chat.mark_sold') }}</button></li>
									{{-- My own list (chat-tidy.js fills the labels): the other person sees none of it --}}
									<li><button type="button" class="dropdown-item" data-chat-tidy="unread"><i class="bi bi-envelope" aria-hidden="true"></i> <span></span></button></li>
									<li><button type="button" class="dropdown-item" data-chat-tidy="muted"><i class="bi bi-bell-slash" aria-hidden="true"></i> <span></span></button></li>
									<li><button type="button" class="dropdown-item" data-chat-tidy="archived"><i class="bi bi-archive" aria-hidden="true"></i> <span></span></button></li>
									<li><hr class="dropdown-divider"></li>
									<li><button type="button" class="dropdown-item" data-chat-block><i class="bi bi-slash-circle" aria-hidden="true"></i> <span data-chat-block-label>{{ trans('chat.block') }}</span></button></li>
									<li><button type="button" class="dropdown-item" data-chat-delete-chat><i class="bi bi-trash3" aria-hidden="true"></i> {{ trans('chat.delete_chat') }}</button></li>
									<li><button type="button" class="dropdown-item text-danger" data-chat-report><i class="bi bi-flag" aria-hidden="true"></i> {{ trans('chat.report') }}</button></li>
								</ul>
							</div>
						</header>

						<div class="adn-chat-offline" data-chat-offline hidden>{{ trans('chat.js.reconnecting') }}</div>

						<a class="adn-chat-listing" data-chat-listing>
							<span class="adn-chat-listing-photo" data-chat-listing-photo aria-hidden="true"><i class="bi bi-image"></i></span>
							<span class="adn-chat-listing-text">
								{{-- "Last enquiry", or "Asking about" before the first message about this listing --}}
								<span class="adn-chat-listing-label" data-chat-listing-label></span>
								<strong class="adn-chat-own-dir" data-chat-listing-title dir="auto"></strong>
								<span data-chat-listing-price></span>
							</span>
							<i class="bi bi-chevron-right adn-chat-listing-go" aria-hidden="true"></i>
						</a>

						<div class="adn-chat-log-wrap">
							{{-- Not a live region either: loading older messages would read them all out again --}}
							<div class="adn-chat-log" data-chat-log role="region" aria-label="{{ trans('chat.messages_label') }}" tabindex="0">
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

						<div class="adn-chat-replying" data-chat-replying hidden>
							<span class="adn-chat-replying-text">
								<strong class="adn-chat-own-dir" data-chat-replying-name dir="auto"></strong>
								<span class="adn-chat-own-dir" data-chat-replying-body dir="auto"></span>
							</span>
							<button type="button" class="adn-chat-icon-btn" data-chat-replying-cancel aria-label="{{ trans('chat.cancel_reply') }}">
								<i class="bi bi-x-lg" aria-hidden="true"></i>
							</button>
						</div>

						{{-- Photos waiting to be sent: picked, pasted or dropped (chat-attach.js) --}}
						<ul class="adn-chat-tray" data-chat-tray aria-label="{{ trans('chat.photos_to_send') }}" data-remove-label="{{ trans('chat.remove_photo') }}" hidden></ul>
						<p class="adn-chat-error" data-chat-error role="alert" hidden></p>

						<form class="adn-chat-composer" data-chat-composer novalidate>
							<input type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple data-chat-photo-input hidden>
							<button type="button" class="adn-chat-icon-btn" data-chat-photo-btn aria-label="{{ trans('chat.add_photo') }}">
								<i class="bi bi-image" aria-hidden="true"></i>
							</button>
							{{-- More to send: an offer on the listing, a saved reply (chat-tools.js fills the menu) --}}
							<div class="dropup adn-chat-plus" data-chat-more>
								<button type="button" class="adn-chat-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ trans('chat.more_actions') }}">
									<i class="bi bi-plus-lg" aria-hidden="true"></i>
								</button>
								<div class="dropdown-menu adn-chat-plus-menu" data-chat-more-menu></div>
							</div>
							<label class="visually-hidden" for="adnChatInput">{{ trans('chat.message_label') }}</label>
							<div class="adn-chat-input-wrap">
								<textarea id="adnChatInput" class="adn-chat-input" rows="1" maxlength="{{ $chatState['limits']['body_max'] }}" placeholder="{{ trans('chat.message_placeholder') }}" data-chat-input autocomplete="off" aria-describedby="adnChatHint"></textarea>
								{{-- Computers only, while writing: Enter sends, so say how to make a new line --}}
								<span class="adn-chat-hint" id="adnChatHint">{!! str_replace(':keys', '<kbd>Shift</kbd> + <kbd>Enter</kbd>', e(trans('chat.new_line_hint'))) !!}</span>
								<span class="adn-chat-count" data-chat-count hidden></span>
							</div>
							<button type="submit" class="adn-chat-send" data-chat-send disabled aria-label="{{ trans('chat.send') }}">
								<i class="bi bi-send-fill" aria-hidden="true"></i>
							</button>
						</form>
						<p class="adn-chat-blocked" data-chat-blocked hidden></p>
						{{-- Shown while photos are dragged over the conversation --}}
						<div class="adn-chat-drop" data-chat-drop aria-hidden="true" hidden>
							<span><i class="bi bi-images" aria-hidden="true"></i> {{ trans('chat.drop_photos') }}</span>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>

	@include('front.chat.partials.report-dialog')
	@include('front.chat.partials.tools-dialog')
	@include('front.chat.partials.privacy-dialog')
	@include('front.chat.partials.viewer')

	<script type="application/json" id="adnChatState">{!! json_encode($chatState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endsection

@push('after_scripts_stack')
	@foreach (['chat-kit', 'chat-inbox', 'chat-log', 'chat-presence', 'chat-reply', 'chat-safety', 'chat-delete', 'chat-drafts', 'chat-guard', 'chat-settings', 'chat-photo', 'chat-attach', 'chat-viewer', 'chat-offers', 'chat-tools', 'chat-tidy', 'chat-privacy', 'chat'] as $chatScript)
		<script src="{{ url('assets/js/app/' . $chatScript . '.js') }}?v={{ @filemtime(public_path('assets/js/app/' . $chatScript . '.js')) }}" defer></script>
	@endforeach
@endpush
