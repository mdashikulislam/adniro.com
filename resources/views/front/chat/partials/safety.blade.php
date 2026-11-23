{{--
	adniro: the safety card at the top of every conversation, as wide as the messages
	(public/assets/css/chat-marks.css): a shield and a title, a button to the safety tips, three habits that
	stop most scams, and at the foot the promise that answers the commonest one, a fake "support" message.
--}}
@php
	$safetyLink = getUrlPageByType('tips');
	$hasSafetyPage = !str_contains($safetyLink, 'href="#"') && !str_contains($safetyLink, 'href=""');
	$safetyTips = [
		'geo-alt'     => trans('chat.safety_tip_meet'),
		'search'      => trans('chat.safety_tip_check'),
		'shield-slash' => trans('chat.safety_tip_never'),
	];
@endphp
<section class="adn-chat-safety" role="note" aria-label="{{ trans('chat.safety_title') }}">
	<header class="adn-chat-safety-head">
		<span class="adn-chat-safety-badge" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
		<strong class="adn-chat-safety-title">{{ trans('chat.safety_title') }}</strong>
		@if ($hasSafetyPage)
			<span class="adn-chat-safety-link"><a {!! $safetyLink !!}>{{ t('chat_safety_link') }}</a></span>
		@endif
	</header>
	<ul class="adn-chat-safety-tips">
		@foreach ($safetyTips as $tipIcon => $tipText)
			<li><i class="bi bi-{{ $tipIcon }}" aria-hidden="true"></i><span>{{ $tipText }}</span></li>
		@endforeach
	</ul>
	<p class="adn-chat-safety-never">
		<i class="bi bi-lock-fill" aria-hidden="true"></i>
		<span>{{ trans('chat.never_asks') }}</span>
	</p>
</section>
