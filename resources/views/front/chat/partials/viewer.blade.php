{{--
	adniro: a chat photo, full size, without leaving the conversation (public/assets/js/app/chat-viewer.js).
	A <dialog>: the browser keeps the focus inside it and closes it with Escape.
--}}
<dialog class="adn-chat-viewer" id="adnChatViewer" aria-label="{{ trans('chat.viewer_label') }}">
	<div class="adn-chat-viewer-bar">
		<span class="adn-chat-viewer-info">
			<strong data-chat-viewer-who></strong>
			<span data-chat-viewer-when></span>
		</span>
		<span class="adn-chat-viewer-count" data-chat-viewer-count aria-live="polite"></span>
		<a class="adn-chat-viewer-btn" data-chat-viewer-open target="_blank" rel="noopener" aria-label="{{ trans('chat.viewer_open') }}" title="{{ trans('chat.viewer_open') }}">
			<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
		</a>
		<button type="button" class="adn-chat-viewer-btn" data-chat-viewer-close aria-label="{{ trans('chat.close') }}" title="{{ trans('chat.close') }}">
			<i class="bi bi-x-lg" aria-hidden="true"></i>
		</button>
	</div>
	<div class="adn-chat-viewer-stage" data-chat-viewer-stage>
		<button type="button" class="adn-chat-viewer-btn adn-chat-viewer-nav is-prev" data-chat-viewer-prev aria-label="{{ trans('chat.viewer_previous') }}">
			<i class="bi bi-chevron-left" aria-hidden="true"></i>
		</button>
		<img class="adn-chat-viewer-img" data-chat-viewer-img alt="">
		<span class="spinner-border adn-chat-viewer-wait" data-chat-viewer-wait aria-hidden="true" hidden></span>
		<button type="button" class="adn-chat-viewer-btn adn-chat-viewer-nav is-next" data-chat-viewer-next aria-label="{{ trans('chat.viewer_next') }}">
			<i class="bi bi-chevron-right" aria-hidden="true"></i>
		</button>
	</div>
</dialog>
