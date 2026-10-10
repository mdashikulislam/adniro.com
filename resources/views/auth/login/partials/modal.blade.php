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
					@include('auth.login.partials.social-buttons')
					
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
