@php
	$siteInfo ??= [];
	$mailDrivers ??= [];
	$mailDriversSelectorsJson ??= '[]';
@endphp

<div class="mb-4 col-md-12 mt-3">
	<h5 class="mb-0 fs-5 border-bottom pb-3">
		<i class="bi bi-envelope"></i> {{ trans('messages.mail_sending_configuration') }}
	</h5>
</div>

{{-- settings[mail][driver] --}}
@include('helpers.forms.fields.select2', [
	'label'       => trans('messages.mail_driver'),
	'name'        => 'settings[mail][driver]',
	'required'    => false,
	'options'     => $mailDrivers,
	'value'       => data_get($siteInfo, 'settings.mail.driver'),
	'placeholder' => 'Select a driver',
	'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
])

{{-- settings[mail][driver_test] --}}
@include('helpers.forms.fields.checkbox', [
	'label'     => trans('messages.mail_driver_test'),
	'name'      => 'settings[mail][driver_test]',
	'switch'    => true,
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.driver_test'),
	'hint'      => trans('admin.mail_driver_test_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mt-4'],
])

@if (array_key_exists('sendmail', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.sendmail'))
		@include('setup.install.site_info.mail_drivers.sendmail')
	@endif
@endif
@if (array_key_exists('smtp', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.smtp'))
		@include('setup.install.site_info.mail_drivers.smtp')
	@endif
@endif
@if (array_key_exists('mailgun', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.mailgun'))
		@include('setup.install.site_info.mail_drivers.mailgun')
	@endif
@endif
@if (array_key_exists('postmark', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.postmark'))
		@include('setup.install.site_info.mail_drivers.postmark')
	@endif
@endif
@if (array_key_exists('ses', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.ses'))
		@include('setup.install.site_info.mail_drivers.ses')
	@endif
@endif
@if (array_key_exists('sparkpost', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.sparkpost'))
		@include('setup.install.site_info.mail_drivers.sparkpost')
	@endif
@endif
@if (array_key_exists('resend', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.resend'))
		@include('setup.install.site_info.mail_drivers.resend')
	@endif
@endif
@if (array_key_exists('mailersend', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.mailersend'))
		@include('setup.install.site_info.mail_drivers.mailersend')
	@endif
@endif
@if (array_key_exists('brevo', $mailDrivers))
	@if (view()->exists('setup.install.site_info.mail_drivers.brevo'))
		@include('setup.install.site_info.mail_drivers.brevo')
	@endif
@endif

@section('after_scripts')
	@parent
	<script>
		let mailDriversSelectors = {!! $mailDriversSelectorsJson !!};
		let mailDriversSelectorsList = Object.values(mailDriversSelectors);
		
		onDocumentReady((event) => {
			let driverEl = document.querySelector('select[name="settings[mail][driver]"]');
			let driverTestEl = document.querySelector('input[type=checkbox][name="settings[mail][driver_test]"]');
			if (!driverEl || !driverTestEl) return;
			
			getDriverFields(driverEl, driverTestEl);
			
			/* On driver element (select2) change|select */
			$(driverEl).on('change', e => getDriverFields(e.target, driverTestEl));
			
			/* On driver test element (checkbox) check */
			driverTestEl.addEventListener('change', e => getDriverFields(driverEl, e.target));
			
			let driverTestParentEl = driverTestEl.closest('div.form-check');
			if (driverTestParentEl) {
				driverTestParentEl.addEventListener('click', e => toggleDriverTestEl(e.target));
			}
		});
		
		function getDriverFields(driverEl, driverTestEl) {
			/* Show the selected driver fields */
			const driverElValue = driverEl.value;
			const selectedDriverSelector = mailDriversSelectors[driverElValue] ?? "";
			/* const driversSelectorsListToHide = mailDriversSelectorsList.filter(item => item !== selectedDriverSelector); */
			
			/* Hide all drivers fields except those of the selected driver */
			/* setElementsVisibility('hide', driversSelectorsListToHide); */
			setElementsVisibility('hide', mailDriversSelectorsList);
			
			if (driverElValue === 'sendmail') {
				/* Show the 'sendmail' driver fields only when the driver validation is enabled */
				/* That allows to use default sendmail parameters if validation is not required */
				if (isElDefined(driverTestEl) && driverTestEl.checked) {
					setElementsVisibility('show', selectedDriverSelector);
				}
			} else {
				setElementsVisibility('show', selectedDriverSelector);
			}
		}
		
		function toggleDriverTestEl(el) {
			if (!el) return;
			
			/* Avoid to apply checkbox checking to the native feature */
			const nativeCheckboxTags = (
				(el.tagName.toLowerCase() === 'label') ||
				(el.tagName.toLowerCase() === 'input' && el.type === 'checkbox')
			);
			if (nativeCheckboxTags) return;
			
			/* If the current element is still a sub-element of searched element, then try to find the searched element */
			const isTheSameEl = (el.tagName.toLowerCase() === 'div' && el.classList.contains('form-check'));
			if (!isTheSameEl) {
				el = el.closest('div.form-check');
			}
			
			const checkboxEl = el.querySelector('input[type=checkbox]');
			if (checkboxEl) {
				if (checkboxEl.tagName.toLowerCase() === 'input' && checkboxEl.type === 'checkbox') {
					checkboxEl.checked = !checkboxEl.checked;
					checkboxEl.dispatchEvent(new Event('change'));
				}
			}
		}
	</script>
@endsection
