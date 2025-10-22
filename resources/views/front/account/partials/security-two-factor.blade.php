@if (isTwoFactorEnabled())
	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<h5 class="card-title mb-0">
					{{ trans('auth.two_factor_settings_title') }}
				</h5>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-12">
						<p class="text-muted">{!! trans('auth.two_factor_settings_info') !!}</p>
					</div>
				</div>
				
				<div class="row d-flex justify-content-center">
					<div class="col-xl-7 col-lg-8 col-md-10 col-sm-12">
						<form name="2faForm" action="{{ urlGen()->accountSecurityTwoFactor() }}" method="POST" role="form">
							@csrf
							@method('PUT')
							
							<input name="user_id" type="hidden" value="{{ $authUser->getAuthIdentifier() }}">
							
							<div class="row">
								{{-- two_factor_enabled --}}
								@php
									$twoFactorEnabled = $authUser->two_factor_enabled ?? null;
									
									$twoFactorStatus = ($twoFactorEnabled == '1') ? trans('auth.two_factor_enabled') : trans('auth.two_factor_disabled');
									$twoFactorLabel = trans('auth.two_factor_status') . ' (<span>' . $twoFactorStatus . '</span>)';
								@endphp
								@include('helpers.forms.fields.checkbox', [
									'label'    => $twoFactorLabel,
									'id'       => 'twoFactorSwitch',
									'name'     => 'two_factor_enabled',
									'switch'   => true,
									'required' => false,
									'value'    => $twoFactorEnabled,
								])
								
								<div id="2faOptions" class="{{ !$twoFactorEnabled ? 'd-none' : '' }}">
									{{-- two_factor_method --}}
									@php
										$twoFactorMethods = [];
										if (isTwoFactorEnabled('email')) {
											$twoFactorMethods[] = ['value' => 'email', 'text' => 'Email'];
										}
										if (isTwoFactorEnabled('sms')) {
											$twoFactorMethods[] = ['value' => 'sms', 'text' => 'SMS'];
										}
										
										$twoFactorMethod = $authUser->two_factor_method ?? null;
									@endphp
									@include('helpers.forms.fields.radio', [
										'label'    => trans('auth.two_factor_method'),
										'name'     => 'two_factor_method',
										'inline'   => true,
										'required' => true,
										'options'  => $twoFactorMethods,
										'value'    => $twoFactorMethod,
									])
									
									{{-- phone --}}
									@if (isTwoFactorEnabled('sms'))
										@php
											$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
											$phoneValue = $authUser->phone ?? null;
											$phoneCountryValue = $authUser->phone_country ?? config('country.code');
											
											$twoFactorMethod = $authUser->two_factor_method ?? null;
											$phoneCanBeShown = ($twoFactorMethod === 'sms' && empty($phoneValue));
										@endphp
										@include('helpers.forms.fields.intl-tel-input', [
											'label'       => trans('auth.phone_number'),
											'id'          => 'phone',
											'name'        => 'phone',
											'required'    => (getAuthField() == 'phone'),
											'placeholder' => null,
											'value'       => $phoneValue,
											'countryCode' => $phoneCountryValue,
											'wrapper'     => ['id' => 'phoneField', 'class' => !$phoneCanBeShown ? 'd-none' : ''],
										])
									@endif
								</div>
								
								{{-- button --}}
								<div class="col-12 mt-3">
									<div class="row">
										<div class="col-md-12">
											<button type="submit" class="btn btn-primary">{{ t('Update') }}</button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endif

@section('after_scripts')
	@parent
	<script>
		/* 2FA translations */
		const lang2fa = {
			enabled: "{{ trans('auth.two_factor_enabled') }}",
			disabled: "{{ trans('auth.two_factor_disabled') }}",
		};
		
		onDocumentReady((event) => {
			
			/* Handle 2FA switch toggle */
			const twoFactorSwitchEl = document.getElementById('twoFactorSwitch');
			const twoFactorOptionsEl = document.getElementById('2faOptions');
			
			if (twoFactorSwitchEl && twoFactorOptionsEl) {
				const twoFactorLabel = twoFactorSwitchEl.nextElementSibling;
				twoFactorSwitchEl.addEventListener('change', function () {
					twoFactorOptionsEl.classList.toggle('d-none', !this.checked);
					const twoFactorLabelSpanEl = twoFactorLabel.querySelector('span');
					if (twoFactorLabelSpanEl) {
						twoFactorLabelSpanEl.textContent = this.checked ? lang2fa.enabled : lang2fa.disabled;
					}
				});
			}
			
			/* Handle 2FA method radio buttons */
			const twoFactorMethodEls = document.querySelectorAll('input[name="two_factor_method"]');
			const phoneFieldEl = document.getElementById('phone');
			if (twoFactorMethodEls.length > 0 && phoneFieldEl) {
				twoFactorMethodEls.forEach(input => {
					input.addEventListener('change', function () {
						if (!isEmpty(phoneFieldEl.value)) {
							return false;
						}
						
						const phoneFieldWrapperEl = document.getElementById('phoneField');
						if (phoneFieldWrapperEl) {
							phoneFieldWrapperEl.classList.toggle('d-none', this.value !== 'sms');
						}
					});
				});
			}
			
		});
	</script>
@endsection
