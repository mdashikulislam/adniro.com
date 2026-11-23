{{--
	adniro: "Chat with seller" as a pop-up on the listing's page (public/assets/js/app/chat-quick.js,
	public/assets/css/chat-quick.css). It opens only when the page is reached from a listing card's chat button
	(the address ends in #chat); the page's own "Chat with seller" buttons go to the chat, as before. The first
	message is sent from here; the conversation then goes on in the chat. A guest gets the sign-in pop-up
	instead; the owner, and a listing with nobody to chat with, get nothing.
--}}
@php
	$chatQuickUrl = chatStartUrl($post);
	$isChatQuickMember = !empty($chatQuickUrl) && !str_starts_with($chatQuickUrl, '#');
	$chatQuickSeller = trim((string)data_get($post, 'contact_name'));
	$chatQuickPhoto = data_get($post, 'user_photo_url');
	$chatQuickPicture = data_get($post, 'picture.url.medium');
	$chatQuickPrice = trim(html_entity_decode(strip_tags((string)data_get($post, 'price_formatted'))));
@endphp
@if (!empty($chatQuickUrl))
	@if ($isChatQuickMember)
		<div class="modal fade adn-quick" id="adnChatQuick" tabindex="-1" aria-labelledby="adnChatQuickTitle" aria-hidden="true"
		     data-start-url="{{ route('chat.start', ['post' => data_get($post, 'id')]) }}"
		     data-failed="{{ trans('chat.quick_failed') }}"
		     data-too-fast="{{ trans('chat.error_too_fast') }}">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<header class="adn-quick-head">
						<span class="adn-quick-avatar" aria-hidden="true">
							@if (!empty($chatQuickPhoto))
								<img src="{{ $chatQuickPhoto }}" alt="" width="48" height="48">
							@else
								{{ mb_strtoupper(mb_substr($chatQuickSeller, 0, 1)) ?: '?' }}
							@endif
						</span>
						<div class="adn-quick-who">
							<span class="adn-quick-eyebrow" id="adnChatQuickTitle">{{ trans('chat.chat_with_seller') }}</span>
							<strong dir="auto">{{ $chatQuickSeller }}</strong>
						</div>
						<button type="button" class="adn-quick-close" data-bs-dismiss="modal" aria-label="{{ trans('chat.close') }}">
							<i class="bi bi-x-lg" aria-hidden="true"></i>
						</button>
					</header>

					<form class="adn-quick-body" data-quick-form novalidate>
						<div class="adn-quick-listing">
							<span class="adn-quick-listing-photo" aria-hidden="true">
								@if (!empty($chatQuickPicture))
									<img src="{{ $chatQuickPicture }}" alt="" width="52" height="52" loading="lazy">
								@else
									<i class="bi bi-image"></i>
								@endif
							</span>
							<span class="adn-quick-listing-text">
								<span>{{ trans('chat.quick_about') }}</span>
								<strong dir="auto">{{ data_get($post, 'title') }}</strong>
								@if ($chatQuickPrice !== '')
									<em>{{ $chatQuickPrice }}</em>
								@endif
							</span>
						</div>

						<div class="adn-quick-chips" role="group" aria-label="{{ trans('chat.quick_replies_label') }}">
							@foreach ((array)trans('chat.quick_replies') as $chatQuickReply)
								<button type="button" data-quick-chip>{{ $chatQuickReply }}</button>
							@endforeach
						</div>

						<label class="visually-hidden" for="adnChatQuickInput">{{ trans('chat.message_label') }}</label>
						<textarea class="adn-quick-input" id="adnChatQuickInput" rows="3" maxlength="{{ \App\Services\Chat\ChatMessages::BODY_MAX }}"
						          placeholder="{{ trans('chat.message_placeholder') }}" data-quick-input></textarea>
						<p class="adn-quick-error" data-quick-error role="alert" hidden></p>

						<div class="adn-quick-actions">
							<a href="{{ $chatQuickUrl }}" class="adn-quick-link">{{ trans('chat.quick_full') }}</a>
							<button type="submit" class="adn-quick-send" data-quick-send disabled>
								<i class="bi bi-send-fill" aria-hidden="true"></i> {{ trans('chat.quick_send') }}
							</button>
						</div>
					</form>

					{{-- After the message has gone --}}
					<div class="adn-quick-sent" data-quick-sent tabindex="-1" hidden>
						<span class="adn-quick-sent-icon" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
						<strong>{{ trans('chat.quick_sent_title') }}</strong>
						<p>{{ trans('chat.quick_sent_text', ['name' => $chatQuickSeller]) }}</p>
						<div class="adn-quick-actions">
							<button type="button" class="adn-quick-link" data-bs-dismiss="modal">{{ trans('chat.quick_keep') }}</button>
							<a href="{{ $chatQuickUrl }}" class="adn-quick-send" data-quick-open>
								<i class="bi bi-chat-dots-fill" aria-hidden="true"></i> {{ trans('chat.quick_open_chat') }}
							</a>
						</div>
					</div>

					<p class="adn-quick-never">
						<i class="bi bi-lock-fill" aria-hidden="true"></i>
						<span>{{ trans('chat.never_asks') }}</span>
					</p>
				</div>
			</div>
		</div>

		@push('after_styles_stack')
			<link href="{{ url('assets/css/chat-quick.css') }}?v={{ @filemtime(public_path('assets/css/chat-quick.css')) }}" rel="stylesheet">
		@endpush
	@endif

	@push('after_scripts_stack')
		<script src="{{ url('assets/js/app/chat-quick.js') }}?v={{ @filemtime(public_path('assets/js/app/chat-quick.js')) }}" defer></script>
	@endpush
@endif
