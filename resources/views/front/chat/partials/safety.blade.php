{{-- adniro: a short safety reminder at the top of every conversation --}}
@php
	$safetyLink = getUrlPageByType('tips');
	$hasSafetyPage = !str_contains($safetyLink, 'href="#"') && !str_contains($safetyLink, 'href=""');
@endphp
<div class="adn-chat-safety" role="note">
	<i class="bi bi-shield-check" aria-hidden="true"></i>
	<span>
		<strong>{{ t('chat_safety_title') }}</strong>
		{{ t('chat_safety_text') }}
		@if ($hasSafetyPage)
			<a {!! $safetyLink !!}>{{ t('chat_safety_link') }}</a>
		@endif
		{{-- The commonest scam in a chat is a fake "support" message --}}
		<span class="adn-chat-safety-never">{{ trans('chat.never_asks') }}</span>
	</span>
</div>
