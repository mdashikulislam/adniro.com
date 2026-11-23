{{--
	adniro: live chat on every page, for signed-in members only: the unread count, the pop-up for a new
	message, and the live connection
	(public/assets/js/app/chat-live.js). Guests and search engines get nothing from this file.
--}}
@auth
	<script type="application/json" id="adnChatLive">{!! json_encode(\App\Services\Chat\ChatLive::browserConfig((int)auth()->id()), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
	<link href="{{ url('assets/css/chat-live.css') }}?v={{ @filemtime(public_path('assets/css/chat-live.css')) }}" rel="stylesheet">
	<script src="{{ url('assets/js/app/chat-live.js') }}?v={{ @filemtime(public_path('assets/js/app/chat-live.js')) }}" defer></script>
@endauth
