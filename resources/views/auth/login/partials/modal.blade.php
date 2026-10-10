@php
	$socialLoginProviders = socialLogin()->isEnabled() ? socialLogin()->providersForConnection(strict: true) : [];
@endphp
<form action="{{ urlGen()->signIn() }}" method="POST" role="form">
	@csrf
	<div class="modal fade login-modal" id="quickLogin" tabindex="-1" aria-labelledby="quickLoginLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">

				<div class="login-modal-hero">
					<button type="button" class="btn-close btn-close-white login-modal-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
					<span class="login-modal-hero-icon"><i class="fa-solid fa-right-to-bracket"></i></span>
					<h4 class="login-modal-title" id="quickLoginLabel">{{ t('login_modal_title') }}</h4>
					<p class="login-modal-subtitle">{{ t('login_modal_subtitle') }}</p>
				</div>

				<div class="modal-body login-modal-body">
					<input type="hidden" name="language_code" value="{{ config('app.locale') }}">

					@if (isset($errors) && $errors->any() && old('quickLoginForm')=='1')
						<div class="alert alert-danger alert-dismissible login-modal-alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<ul class="mb-0 list-unstyled">
								@foreach($errors->all() as $error)
									<li class="lh-lg"><i class="bi bi-exclamation-circle me-1"></i>{!! $error !!}</li>
								@endforeach
							</ul>
						</div>
					@endif

					{{-- social login --}}
					@if (!empty($socialLoginProviders))
						<div class="login-modal-social">
							@foreach($socialLoginProviders as $provider => $providerData)
								@php
									$url = data_get($providerData, 'url');
									$label = data_get($providerData, 'label');
									$iconClass = data_get($providerData, 'iconClass');
									$providerName = data_get($providerData, 'name', ucfirst($provider));
								@endphp
								<a href="{{ $url }}" class="login-social-btn login-social-{{ $provider }}" title="{{ strip_tags($label) ?: $providerName }}">
									@if ($provider == 'google')
										<svg class="login-social-logo" viewBox="0 0 48 48" aria-hidden="true">
											<path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
											<path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
											<path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
											<path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/>
										</svg>
									@else
										<i class="{{ $iconClass }} login-social-logo"></i>
									@endif
									<span>{!! !empty(strip_tags($label)) ? $label : $providerName !!}</span>
								</a>
							@endforeach
						</div>
						<div class="login-modal-divider"><span>{{ t('or_continue_with_email') }}</span></div>
					@endif

					{{-- email --}}
					@php
						$labelRight = '';
						if (isPhoneAsAuthFieldEnabled()) {
							$labelRight .= '<a href="" class="link-primary text-decoration-none auth-field" data-auth-field="phone" data-ignore-guard="true">';
							$labelRight .= trans('auth.login_with_phone');
							$labelRight .= '</a>';
						}
						$emailValue = session()->has('email') ? session('email') : null;
					@endphp
					@include('helpers.forms.fields.text', [
						'label'             => trans('auth.email'),
						'labelRightContent' => $labelRight,
						'id'                => 'mEmail',
						'name'              => 'email',
						'required'          => (getAuthField() == 'email'),
						'placeholder'       => trans('auth.email_or_username'),
						'value'             => $emailValue,
						'prefix'            => '<i class="bi bi-envelope"></i>',
						'suffix'            => null,
						'wrapper'           => ['class' => 'auth-field-item'],
					])

					{{-- phone --}}
					@if (isPhoneAsAuthFieldEnabled())
						@php
							$labelRight = '<a href="" class="link-primary text-decoration-none auth-field" data-auth-field="email" data-ignore-guard="true">';
							$labelRight .= trans('auth.login_with_email');
							$labelRight .= '</a>';

							$phoneValue = session()->has('phone') ? session('phone') : null;
							$phoneCountryValue = config('country.code');
						@endphp
						@include('helpers.forms.fields.intl-tel-input', [
							'label'             => trans('auth.phone_number'),
							'labelRightContent' => $labelRight,
							'id'                => 'mPhone',
							'name'              => 'phone',
							'required'          => (getAuthField() == 'phone'),
							'placeholder'       => null,
							'value'             => $phoneValue,
							'attributes'        => ['class' => 'form-control m-phone'],
							'countryCode'       => $phoneCountryValue,
							'independentJs'     => true,
							'wrapper'           => ['class' => 'auth-field-item'],
						])
					@endif

					{{-- auth_field --}}
					<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">

					{{-- password --}}
					@include('helpers.forms.fields.password', [
						'label'          => trans('auth.password'),
						'id'             => 'mPassword',
						'name'           => 'password',
						'placeholder'    => trans('auth.password'),
						'required'       => true,
						'value'          => null,
						'prefix'         => '<i class="bi bi-lock"></i>',
						'togglePassword' => 'icon',
						'hint'           => false,
					])

					{{-- remember --}}
					@php
						$labelRight = '<a href="' . urlGen()->passwordForgot() . '" class="login-modal-forgot">';
						$labelRight .= trans('auth.forgot_password');
						$labelRight .= '</a>';
					@endphp
					@include('helpers.forms.fields.checkbox', [
						'label'             => trans('auth.remember_me'),
						'labelRightContent' => $labelRight,
						'id'                => 'rememberMe2',
						'name'              => 'remember',
						'value'             => null,
						'wrapper'           => ['class' => 'login-modal-remember'],
					])

					{{-- captcha --}}
					<div class="login-modal-captcha">
						@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
					</div>

					<input type="hidden" name="quickLoginForm" value="1">

					<button type="submit" class="btn login-modal-submit">
						{{ trans('auth.log_in') }} <i class="fa-solid fa-arrow-right"></i>
					</button>
				</div>

				<div class="login-modal-footer">
					{{ trans('auth.dont_have_account') }}
					<a href="{{ urlGen()->signUp() }}">{{ trans('auth.create_account') }}</a>
				</div>
			</div>
		</div>
	</div>
</form>
