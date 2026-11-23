{{-- adniro: what others see of me in the chat, and the people I blocked (public/assets/js/app/chat-privacy.js) --}}
<div class="modal fade" id="adnChatPrivacy" tabindex="-1" aria-labelledby="adnChatPrivacyTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content adn-chat-tools adn-chat-privacy">
			<div class="modal-header">
				<h2 class="modal-title fs-5 fw-bold" id="adnChatPrivacyTitle">{{ trans('chat.privacy_title') }}</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('chat.close') }}"></button>
			</div>
			<div class="modal-body">
				{{-- Both are saved as soon as the switch moves, and both work both ways --}}
				<section class="adn-chat-tools-part">
					<label class="adn-chat-switch">
						<span class="adn-chat-switch-text">
							<strong>{{ trans('chat.privacy_online_label') }}</strong>
							<span>{{ trans('chat.privacy_online_hint') }}</span>
						</span>
						<span class="form-check form-switch m-0">
							<input type="checkbox" class="form-check-input" role="switch" data-chat-privacy-online @checked(!empty($chatState['privacy']['online']))>
						</span>
					</label>
					<label class="adn-chat-switch">
						<span class="adn-chat-switch-text">
							<strong>{{ trans('chat.privacy_seen_label') }}</strong>
							<span>{{ trans('chat.privacy_seen_hint') }}</span>
						</span>
						<span class="form-check form-switch m-0">
							<input type="checkbox" class="form-check-input" role="switch" data-chat-privacy-seen @checked(!empty($chatState['privacy']['seen']))>
						</span>
					</label>
				</section>

				<section class="adn-chat-tools-part">
					<h3 class="adn-chat-tools-title">{{ trans('chat.blocked_people') }}</h3>
					<p class="adn-chat-tools-hint">{{ trans('chat.blocked_people_hint') }}</p>
					<ul class="adn-chat-blocked-list" data-chat-blocked-list aria-live="polite"></ul>
				</section>
			</div>
		</div>
	</div>
</div>
