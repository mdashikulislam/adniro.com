@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
	<img width="200" src="{{ config('settings.app.logo_url') }}" alt="{{ config('settings.app.name', config('app.name')) }}" style="max-width: 200px; height: auto;">
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
@php
	// adniro: help, safety and email preference links in every email
	$mailAppName = config('settings.app.name', config('app.name'));
	$mailSafetyUrl = null;
	try {
		$mailSafetyPage = \App\Models\Page::query()->where('type', 'tips')->first();
		$mailSafetyUrl = $mailSafetyPage ? urlGen()->page($mailSafetyPage) : null;
	} catch (\Throwable $e) {
		report($e);
	}
	$mailLinks = array_filter([
		'[' . t('mail_footer_help') . '](' . url('contact') . ')',
		$mailSafetyUrl ? '[' . t('mail_footer_safety') . '](' . $mailSafetyUrl . ')' : null,
		'[' . t('mail_footer_preferences') . '](' . url(urlGen()->getAccountBasePath() . '/preferences') . ')',
	]);
@endphp
{{ implode(' · ', $mailLinks) }}

{{ t('mail_footer_reason', ['appName' => $mailAppName]) }}

© {{ date('Y') }} {{ $mailAppName }}. @lang('mail.All rights reserved.')
@endcomponent
@endslot
@endcomponent
