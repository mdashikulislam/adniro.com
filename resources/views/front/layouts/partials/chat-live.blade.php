{{--
	adniro: live chat on every page, for signed-in members only: the unread badge, and the live connection
	(public/assets/js/app/chat-live.js). Guests and search engines get nothing from this file.
--}}
@auth
	<script type="application/json" id="adnChatLive">{!! json_encode(\App\Services\Chat\ChatLive::browserConfig((int)auth()->id()), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
	<script src="{{ url('assets/js/app/chat-live.js') }}?v={{ @filemtime(public_path('assets/js/app/chat-live.js')) }}" defer></script>
@endauth
