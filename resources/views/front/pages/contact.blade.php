@extends('front.layouts.master')
@php
	$authUser ??= auth()->user();
@endphp
@section('search')
	@parent
	@include('front.pages.contact.intro')
@endsection

@section('body_class', 'page-contact')
@section('content')
	@include('front.common.spacer')
	@php
		$appEmail = config('settings.app.email');
		$appPhone = config('settings.app.phone_number');
		$countryName = config('country.name');
		$socialLinks = [
			'facebook_page_url' => ['icon' => 'fa-brands fa-facebook-f', 'label' => 'Facebook'],
			'twitter_url'       => ['icon' => 'fa-brands fa-x-twitter', 'label' => 'X (Twitter)'],
			'instagram_url'     => ['icon' => 'fa-brands fa-instagram', 'label' => 'Instagram'],
			'linkedin_url'      => ['icon' => 'fa-brands fa-linkedin-in', 'label' => 'LinkedIn'],
			'tiktok_url'        => ['icon' => 'fa-brands fa-tiktok', 'label' => 'TikTok'],
			'youtube_url'       => ['icon' => 'fa-brands fa-youtube', 'label' => 'YouTube'],
			'pinterest_url'     => ['icon' => 'fa-brands fa-pinterest-p', 'label' => 'Pinterest'],
		];
		$socialLinks = collect($socialLinks)
			->map(fn ($item, $key) => array_merge($item, ['url' => config('settings.social_link.' . $key)]))
			->filter(fn ($item) => !empty($item['url']));
	@endphp
	<div class="main-container contact-page">
		<div class="container">
			
			@if (isset($errors) && $errors->any())
				<div class="alert alert-danger alert-dismissible contact-alert">
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
					<h5 class="fw-bold">{{ t('validation_errors_title') }}</h5>
					<ul class="mb-0">
						@foreach ($errors->all() as $error)
							<li>{!! $error !!}</li>
						@endforeach
					</ul>
				</div>
			@endif
			
			{{-- Page title --}}
			<div class="contact-hero">
				<span class="contact-hero-icon"><i class="bi bi-chat-heart-fill"></i></span>
				<div>
					<h1 class="contact-hero-title">{{ t('Contact Us') }}</h1>
					<p class="contact-hero-text">{{ t('contact_intro') }}</p>
				</div>
			</div>
			
			<div class="row g-4">
				{{-- Left: channels --}}
				<div class="col-lg-4">
					<aside class="contact-aside">
						<div class="contact-aside-card">
							<span class="contact-aside-glow" aria-hidden="true"></span>
							<h2 class="contact-aside-title">{{ t('get_in_touch') }}</h2>
							<p class="contact-aside-text">{{ t('contact_response_time') }}</p>
							<ul class="contact-channels list-unstyled">
								@if (!empty($appEmail))
									<li>
										<a href="mailto:{{ $appEmail }}" class="contact-channel">
											<span class="contact-channel-icon"><i class="bi bi-envelope-fill"></i></span>
											<span class="contact-channel-body">
												<span class="contact-channel-label">{{ trans('auth.email') }}</span>
												<span class="contact-channel-value">{{ $appEmail }}</span>
											</span>
										</a>
									</li>
								@endif
								@if (!empty($appPhone))
									<li>
										<a href="tel:{{ $appPhone }}" class="contact-channel">
											<span class="contact-channel-icon"><i class="bi bi-telephone-fill"></i></span>
											<span class="contact-channel-body">
												<span class="contact-channel-label">{{ trans('auth.phone_number') }}</span>
												<span class="contact-channel-value">{{ $appPhone }}</span>
											</span>
										</a>
									</li>
								@endif
								@if (!empty($countryName))
									<li>
										<span class="contact-channel">
											<span class="contact-channel-icon"><i class="bi bi-geo-alt-fill"></i></span>
											<span class="contact-channel-body">
												<span class="contact-channel-label">{{ t('location') }}</span>
												<span class="contact-channel-value">{{ $countryName }}</span>
											</span>
										</span>
									</li>
								@endif
							</ul>
							
							@if ($socialLinks->isNotEmpty())
								<div class="contact-social">
									<span class="contact-social-label">{{ t('follow_us') }}</span>
									<div class="contact-social-links">
										@foreach ($socialLinks as $item)
											<a href="{{ $item['url'] }}" target="_blank" rel="noopener nofollow" title="{{ $item['label'] }}" aria-label="{{ $item['label'] }}">
												<i class="{{ $item['icon'] }}"></i>
											</a>
										@endforeach
									</div>
								</div>
							@endif
						</div>
					</aside>
				</div>
				
				{{-- Right: form --}}
				<div class="col-lg-8">
					<div class="contact-form-card">
						<div class="contact-form-head">
							<span class="contact-form-head-icon"><i class="bi bi-send-fill"></i></span>
							<div>
								<h2 class="contact-form-title">{{ t('contact_form_title') }}</h2>
								<p class="contact-form-hint">{{ t('contact_form_hint') }}</p>
							</div>
						</div>
						
						<form action="{{ urlGen()->contact() }}" method="post" class="{{ unsavedFormGuard() }} needs-validation contact-form">
							@csrf
							@honeypot
							
							<div class="row">
								{{-- name --}}
								@include('helpers.forms.fields.text', [
									'label'       => t('Name'),
									'name'        => 'name',
									'placeholder' => t('enter_your_name'),
									'required'    => true,
									'value'       => $authUser->name ?? null,
									'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
								])
								
								{{-- company_name --}}
								@include('helpers.forms.fields.text', [
									'label'       => t('company_name'),
									'name'        => 'company_name',
									'placeholder' => t('company_name'),
									'value'       => null,
									'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
								])
								
								{{-- email --}}
								@include('helpers.forms.fields.email', [
									'label'       => trans('auth.email'),
									'id'          => 'contactEmail',
									'name'        => 'email',
									'placeholder' => trans('auth.email_address'),
									'required'    => true,
									'value'       => $authUser->email ?? null,
									'attributes'  => ['data-valid-type' => 'email'],
									'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
								])
								
								{{-- phone --}}
								@include('helpers.forms.fields.tel', [
									'label'       => trans('auth.phone_number'),
									'name'        => 'phone',
									'placeholder' => trans('auth.phone_number'),
									'required'    => true,
									'value'       => $authUser->phone ?? null,
									'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
								])
								
								{{-- message --}}
								@include('helpers.forms.fields.textarea', [
									'label'         => t('Message'),
									'name'          => 'message',
									'placeholder'   => t('enter_your_message'),
									'required'      => true,
									'value'         => null,
									'attributes'    => ['rows' => 7],
									'pluginOptions' => ['height' => 200]
								])
								
								{{-- captcha --}}
								@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
								
								{{-- button --}}
								<div class="col-12 mt-3 contact-form-actions">
									<button type="submit" class="btn btn-primary contact-submit-btn">
										<i class="bi bi-send"></i> {{ t('send_message') }}
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@section('after_scripts')
	<script>
		onDocumentReady((event) => {
			formValidate("form", formValidateOptions);
		});
	</script>
@endsection
