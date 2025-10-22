@php
	$authUser = auth()->check() ? auth()->user() : null;
	$isLoggedUser = !empty($authUser) ? 'true' : 'false';
	$isLoggedAdmin = doesUserHavePermission($authUser, \App\Models\Permission::getStaffPermissions()) ? 'true' : 'false';
@endphp
<script>
	{{-- Init. Root Vars --}}
	var siteUrl = '{{ url('/') }}';
	var languageCode = '{{ config('app.locale') }}';
	var isLoggedUser = {{ $isLoggedUser }};
	var isLoggedAdmin = {{ $isLoggedAdmin }};
	var isAdminPanel = {{ isAdminPanel() ? 'true' : 'false' }};
	var demoMode = {{ isDemoDomain() ? 'true' : 'false' }};
	var demoMessage = '{{ addcslashes(t('demo_mode_message'), "'") }}';
	
	{{-- Cookie Parameters --}}
	var cookieParams = {
		expires: {{ (int)config('settings.other.cookie_expiration') }},
		path: "{{ config('session.path', '/') }}",
		domain: "{{ !empty(config('session.domain')) ? config('session.domain') : getCookieDomain() }}", {{-- Need to be removed to solve some issues --}}
		secure: {{ config('session.secure') ? 'true' : 'false' }},
		sameSite: "{{ config('session.same_site') }}"
	};
	
	{{-- Init. Translation Vars --}}
	var langLayout = {
		loading: "{{ t('loading_wd') }}",
		errorFound: "{{ t('error_found') }}",
		refresh: "{{ t('refresh') }}",
		confirm: {
			button: {
				yes: "{{ t('confirm_button_yes') }}",
				no: "{{ t('confirm_button_no') }}",
				ok: "{{ t('confirm_button_ok') }}",
				cancel: "{{ t('confirm_button_cancel') }}"
			},
			message: {
				question: "{{ t('confirm_message_question') }}",
				success: "{{ t('confirm_message_success') }}",
				error: "{{ t('confirm_message_error') }}",
				errorAbort: "{{ t('confirm_message_error_abort') }}",
				cancel: "{{ t('confirm_message_cancel') }}"
			}
		},
		waitingDialog: {
			loading: {
				title: "{{ t('waitingDialog_loading_title') }}",
				text: "{{ t('waitingDialog_loading_text') }}"
			},
			complete: {
				title: "{{ t('waitingDialog_complete_title') }}",
				text: "{{ t('waitingDialog_complete_text') }}"
			}
		},
		hideMaxListItems: {
			moreText: "{{ t('View More') }}",
			lessText: "{{ t('View Less') }}"
		},
		select2: {
			errorLoading: function () {
				return "{!! t('The results could not be loaded') !!}"
			},
			inputTooLong: function (e) {
				let t = e.input.length - e.maximum, n = "{!! t('Please delete X character') !!}";
				n = n.replace('{charsLength}', t.toString());
				
				return t != 1 && (n += 's'), n
			},
			inputTooShort: function (e) {
				let t = e.minimum - e.input.length, n = "{!! t('Please enter X or more characters') !!}";
				n = n.replace('{minCharsLength}', t.toString());
				
				return n
			},
			loadingMore: function () {
				return "{!! t('Loading more results') !!}"
			},
			maximumSelected: function (e) {
				let maxItems = e.maximum;
				let t = "{!! t('You can only select N item') !!}";
				t = t.replace('{maxItems}', maxItems.toString());
				
				return maxItems != 1 && (t += 's'), t
			},
			noResults: function () {
				return "{!! t('no_results') !!}"
			},
			searching: function () {
				return "{!! t('Searching') !!}"
			}
		},
		themePreference: {
			light: "{{ t('theme_preference_light') }}",
			dark: "{{ t('theme_preference_dark') }}",
			system: "{{ t('theme_preference_system') }}",
			success: "{{ t('theme_preference_success') }}",
			empty: "{{ t('theme_preference_empty') }}",
			error: "{{ t('theme_preference_error') }}",
		},
		location: {
			area: "{{ t('area') }}"
		},
		autoComplete: {
			searchCities: "{{ t('search_cities') }}",
			enterMinimumChars: (threshold) => `{{ t('enter_minimum_chars') }}`,
			noResultsFor: (query) => {
				query = `<strong>${query}</strong>`;
				return `{{ t('no_results_for') }}`
			},
		},
		payment: {
			submitBtnLabel: {
				pay: "{{ t('Pay') }}",
				submit: "{{ t('submit') }}",
			},
		},
		unsavedFormGuard: {
			error_form_not_found: "{{ t('unsaved_form_guard.error_form_not_found') }}",
			unsaved_changes_prompt: "{{ t('unsaved_form_guard.unsaved_changes_prompt') }}",
		},
	};
	
	const formValidateOptions = {
		formErrorMessage: "{{ t('formValidation.formErrorMessage') }}",
		defaultErrors: {
			required: "{{ t('formValidation.defaultErrors.required') }}",
			validator: "{{ t('formValidation.defaultErrors.validator') }}",
		},
		errors: {
			alphanumeric: "{{ t('formValidation.errors.alphanumeric') }}",
			numeric: "{{ t('formValidation.errors.numeric') }}",
			email: "{{ t('formValidation.errors.email') }}",
			url: "{{ t('formValidation.errors.url') }}",
			username: "{{ t('formValidation.errors.username') }}",
			password: "{{ t('formValidation.errors.password') }}",
			date: "{{ t('formValidation.errors.date') }}",
			time: "{{ t('formValidation.errors.time') }}",
			cardExpiry: "{{ t('formValidation.errors.cardExpiry') }}",
			cardCvc: "{{ t('formValidation.errors.cardCvc') }}",
		},
	};
</script>
