{{-- adniro: a member's own chat tools: the away message and the saved replies (public/assets/js/app/chat-tools.js) --}}
<div class="modal fade" id="adnChatTools" tabindex="-1" aria-labelledby="adnChatToolsTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
		<form class="modal-content adn-chat-tools" data-chat-tools-form novalidate>
			<div class="modal-header">
				<h2 class="modal-title fs-5 fw-bold" id="adnChatToolsTitle">{{ trans('chat.tools_title') }}</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('chat.report_cancel') }}"></button>
			</div>
			<div class="modal-body">
				<section class="adn-chat-tools-part">
					<div class="adn-chat-tools-head">
						<label class="adn-chat-tools-title" for="adnChatAwayMessage">{{ trans('chat.away_label') }}</label>
						<span class="form-check form-switch m-0">
							<input type="checkbox" class="form-check-input" role="switch" data-chat-away-on aria-label="{{ trans('chat.away_switch') }}">
						</span>
					</div>
					<p class="adn-chat-tools-hint">{{ trans('chat.away_hint') }}</p>
					<textarea class="form-control" id="adnChatAwayMessage" rows="3" maxlength="{{ $chatState['limits']['tools_text_max'] }}"
					          placeholder="{{ trans('chat.away_placeholder') }}" data-chat-away-message></textarea>
				</section>

				<section class="adn-chat-tools-part">
					<h3 class="adn-chat-tools-title">{{ trans('chat.saved_replies') }}</h3>
					<p class="adn-chat-tools-hint">{{ trans('chat.saved_hint') }}</p>
					<div class="adn-chat-saved-list" data-chat-saved-list data-placeholder="{{ trans('chat.saved_placeholder') }}"
					     data-remove-label="{{ trans('chat.saved_remove') }}" data-reply-label="{{ trans('chat.saved_replies') }}"></div>
					<button type="button" class="btn btn-sm btn-outline-primary" data-chat-saved-add>
						<i class="bi bi-plus-lg" aria-hidden="true"></i> {{ trans('chat.saved_add') }}
					</button>
				</section>

				<p class="adn-chat-error mt-3 mb-0" data-chat-tools-error role="alert" hidden></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ trans('chat.report_cancel') }}</button>
				<button type="submit" class="btn btn-primary" data-chat-tools-save>{{ trans('chat.tools_save') }}</button>
			</div>
		</form>
	</div>
</div>
