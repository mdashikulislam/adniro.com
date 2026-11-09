<div class="col-12">
	<div class="card account-card">
		<div class="card-header account-card-head">
			<span class="account-card-icon"><i class="bi bi-person-vcard-fill"></i></span>
			<h5 class="card-title mb-0">{{ t('Account Details') }}</h5>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12">
					<form name="details" action="{{ urlGen()->accountProfile() }}" method="POST" role="form">
						@csrf
						@method('PUT')
						
						{{-- Public profile link --}}
						@php
							$detailsUsername = $authUser->username ?? null;
							$detailsProfileUrl = !empty($detailsUsername) ? urlGen()->user(['username' => $detailsUsername]) : null;
						@endphp
						@if (!empty($detailsProfileUrl))
							<div class="account-public-link">
								<span class="account-public-link-icon"><i class="bi bi-link-45deg"></i></span>
								<span class="account-public-link-text">
									<span class="account-public-link-label">{{ t('public_profile') }}</span>
									<a href="{{ $detailsProfileUrl }}" target="_blank" rel="noopener" class="account-public-link-url">
										{{ preg_replace('#^https?://(www\.)?#i', '', $detailsProfileUrl) }}
									</a>
								</span>
								<span class="account-public-link-actions">
									<button type="button"
									        class="btn account-public-link-btn"
									        id="copyProfileLinkBtn"
									        data-url="{{ $detailsProfileUrl }}"
									        data-copied="{{ t('link_copied') }}"
									>
										<i class="bi bi-clipboard"></i> <span>{{ t('copy_link') }}</span>
									</button>
									<a href="{{ $detailsProfileUrl }}" target="_blank" rel="noopener" class="btn account-public-link-btn is-primary">
										<i class="bi bi-box-arrow-up-right"></i> <span>{{ t('view_public_profile') }}</span>
									</a>
								</span>
							</div>
						@endif
						
						<div class="row">
							{{-- gender_id --}}
							@include('helpers.forms.fields.select2', [
								'label'           => t('gender'),
								'id'              => 'genderId',
								'name'            => 'gender_id',
								'required'        => false,
								'placeholder'     => t('Select'),
								'options'         => $genders ?? [],
								'optionValueName' => 'id',
								'optionTextName'  => 'title',
								'value'           => $authUser->gender_id ?? null,
								'baseClass'       => ['wrapper' => 'mb-3 col-md-6'],
							])
							
							{{-- name --}}
							@include('helpers.forms.fields.text', [
								'label'       => t('Name'),
								'name'        => 'name',
								'placeholder' => t('enter_your_name'),
								'required'    => true,
								'value'       => $authUser->name ?? null,
								'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
							])
							
							{{-- username (read-only: it can't be changed) --}}
							<div class="mb-3 col-md-12">
								<label class="form-label" for="usernameLocked">{{ trans('auth.username') }}</label>
								<div class="account-locked-field" id="usernameLocked">
									<span class="account-locked-field-at">@</span>
									<span class="account-locked-field-value">{{ $authUser->username ?? '--' }}</span>
									<span class="account-locked-field-lock"><i class="bi bi-lock-fill"></i></span>
								</div>
								<div class="form-text">{{ t('username_locked_hint') }}</div>
							</div>
							
							{{-- about --}}
							@include('helpers.forms.fields.textarea', [
								'label'         => t('profile_description'),
								'id'            => 'about',
								'name'          => 'about',
								'placeholder'   => t('profile_description_placeholder'),
								'required'      => false,
								'value'         => $authUser->about ?? null,
								'attributes'    => ['maxlength' => 500, 'rows' => 4],
								'pluginOptions' => ['height' => 130],
								'hint'          => t('profile_description_hint', ['max' => 500]) . ' <span id="aboutCounter" class="fw-semibold"></span>',
								'baseClass'     => ['wrapper' => 'mb-3 col-md-12'],
							])
							
							{{-- auth_field (as notification channel) --}}
							@php
								$authFields = getAuthFields(true);
								$authFields = collect($authFields)
									->map(fn($item, $key) => ['value' => $key, 'text' => $item])
									->toArray();
								
								$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel(true);
								$authFieldValue = $authUser->auth_field ?? getAuthField();
								$authFieldValue = ($usersCanChooseNotifyChannel) ? old('auth_field', $authFieldValue) : $authFieldValue;
							@endphp
							@if ($usersCanChooseNotifyChannel)
								@include('helpers.forms.fields.radio', [
									'label'    => trans('auth.notifications_channel'),
									'id'       => 'authField-',
									'name'     => 'auth_field',
									'inline'   => true,
									'required' => true,
									'options'  => $authFields,
									'value'    => $authFieldValue,
									'attributes' => ['class' => 'auth-field-input'],
									'hint'       => trans('auth.notifications_channel_hint'),
								])
							@else
								<input id="authField-{{ $authFieldValue }}" name="auth_field" type="hidden" value="{{ $authFieldValue }}">
							@endif
							
							@php
								$forceToDisplay = isBothAuthFieldsCanBeDisplayed() ? ' force-to-display' : '';
							@endphp
							
							{{-- email --}}
							@include('helpers.forms.fields.email', [
								'label'       => trans('auth.email'),
								'id'          => 'email',
								'name'        => 'email',
								'required'    => (getAuthField() == 'email'),
								'placeholder' => t('enter_your_email'),
								'value'       => $authUser->email ?? null,
								'prefix'      => '<i class="fa-regular fa-envelope"></i>',
								'suffix'      => null,
								'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
								'baseClass'   => ['wrapper' => 'mb-3 col-md-12'],
							])
							
							{{-- phone --}}
							@php
								$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
								$phoneValue = $authUser->phone ?? null;
								$phoneCountryValue = $authUser->phone_country ?? config('country.code');
								
								// phone_hidden
								$phoneHiddenValue = old('phone_hidden', $authUser->phone_hidden ?? null);
								$phoneHiddenChecked = ($phoneHiddenValue == '1') ? ' checked' : '';
								$suffix = '<input id="phoneHidden" name="phone_hidden" type="checkbox" value="1"' . $phoneHiddenChecked . '>';
								$suffix .= '&nbsp;<small>' . t('Hide') . '</small>';
							@endphp
							@include('helpers.forms.fields.intl-tel-input', [
								'label'       => trans('auth.phone_number'),
								'id'          => 'phone',
								'name'        => 'phone',
								'required'    => (getAuthField() == 'phone'),
								'placeholder' => null,
								'value'       => $phoneValue,
								'countryCode' => $phoneCountryValue,
								'suffix'      => $suffix,
								'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
								'baseClass'   => ['wrapper' => 'mb-3 col-md-12'],
							])
							
							{{-- country_code --}}
							<input type="hidden" name="country_code" value="{{ $authUser->country_code ?? null }}">
							
							{{-- button --}}
							<div class="col-12 mt-2 account-form-actions">
								<button type="submit" class="btn btn-primary account-submit-btn">
									<i class="bi bi-check2-circle"></i> {{ t('Update') }}
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
		
		{{-- Public profile link: copy --}}
		onDocumentReady((event) => {
			const copyBtn = document.getElementById('copyProfileLinkBtn');
			if (!copyBtn) {
				return;
			}
			copyBtn.addEventListener('click', async () => {
				try {
					await navigator.clipboard.writeText(copyBtn.dataset.url);
					jsAlert(copyBtn.dataset.copied, 'success');
				} catch (error) {
					window.prompt('', copyBtn.dataset.url);
				}
			});
		});
		
		{{-- Description: characters counter --}}
		onDocumentReady((event) => {
			const aboutEl = document.getElementById('about');
			const counterEl = document.getElementById('aboutCounter');
			if (!aboutEl || !counterEl) {
				return;
			}
			const max = parseInt(aboutEl.getAttribute('maxlength') || '500', 10);
			const refresh = () => {
				counterEl.textContent = aboutEl.value.length + ' / ' + max;
			};
			aboutEl.addEventListener('input', refresh);
			refresh();
		});
	</script>
@endsection
