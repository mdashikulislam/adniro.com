<form action="{{ urlGen()->signIn() }}" method="POST" role="form">
	@csrf
	<div class="modal fade" id="quickLogin" tabindex="-1" aria-labelledby="quickLoginLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-scrollable">
			<div class="modal-content">
				
				<div class="modal-header px-3">
					<h4 class="modal-title fs-5 fw-bold" id="quickLoginLabel">
						<i class="fa-solid fa-right-to-bracket"></i> {{ trans('auth.log_in') }}
					</h4>
					
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
				</div>
				
				<div class="modal-body">
					<div class="row">
						<div class="col-12">
							<input type="hidden" name="language_code" value="{{ config('app.locale') }}">
							
							@if (isset($errors) && $errors->any() && old('quickLoginForm')=='1')
								<div class="alert alert-danger alert-dismissible">
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
									<ul class="mb-0 list-unstyled">
										@foreach($errors->all() as $error)
											<li class="lh-lg"><i class="bi bi-check-lg me-1"></i>{!! $error !!}</li>
										@endforeach
									</ul>
								</div>
							@endif
							
							@include('auth.login.partials.social', ['socialCol' => 12, 'page' => 'modal'])
							@php
								$mtAuth = !socialLogin()->isEnabled() ? ' mt-3' : '';
							@endphp
							
							
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
								'prefix'            => '<i class="bi bi-person"></i>',
								'suffix'            => null,
								'wrapper'           => ['class' => 'auth-field-item' . $mtAuth],
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
									'wrapper'           => ['class' => 'auth-field-item' . $mtAuth],
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
								'prefix'         => '<i class="bi bi-asterisk"></i>',
								'togglePassword' => 'icon',
								'hint'           => false,
							])
							
							{{-- remember --}}
							@php
								$labelRight = '<a href="' . urlGen()->passwordForgot() . '" class="' . linkClass() . '">';
								$labelRight .= trans('auth.forgot_password');
								$labelRight .= '</a>';
								$labelRight .= '<br>';
								$labelRight .= '<a href="' . urlGen()->signUp() . '" class="' . linkClass() . '">';
								$labelRight .= trans('auth.create_account');
								$labelRight .= '</a>';
							@endphp
							@include('helpers.forms.fields.checkbox', [
								'label'             => trans('auth.remember_me'),
								'labelRightContent' => $labelRight,
								'id'                => 'rememberMe2',
								'name'              => 'remember',
								'value'             => null,
								'wrapper'           => ['class' => 'mt-4'],
							])
							
							{{-- captcha --}}
							@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
							
							<input type="hidden" name="quickLoginForm" value="1">
							
						</div>
					</div>
				</div>
				
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary float-end">{{ trans('auth.log_in') }}</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ t('Cancel') }}</button>
				</div>
			</div>
		</div>
	</div>
</form>
