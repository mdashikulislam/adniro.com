{{-- adniro: a short safety reminder at the top of every conversation --}}
@php
	$safetyLink = getUrlPageByType('tips');
	$hasSafetyPage = !str_contains($safetyLink, 'href="#"') && !str_contains($safetyLink, 'href=""');
@endphp
<div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 mb-3 small chat-safety-banner" role="note">
	<i class="bi bi-shield-exclamation fs-5 lh-1 mt-1" aria-hidden="true"></i>
	<div>
		<strong>{{ t('chat_safety_title') }}</strong>
		{{ t('chat_safety_text') }}
		@if ($hasSafetyPage)
			<a {!! $safetyLink !!}>{{ t('chat_safety_link') }}</a>
		@endif
	</div>
</div>
