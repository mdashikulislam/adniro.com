@php
	$langCode ??= ($languageCode ?? null);
	$langCode = $langCode ?? config('app.locale', session('langCode'));
	$langDirection = config('lang.direction');
	$isRtl = $pluginOptions['rtl'] ?? (($langDirection == 'rtl') ? 'true' : 'false');
	
	$fields ??= [];
	$errors ??= [];
	$oldInput ??= [];
	
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
	$fiFileLoadingMessage ??= t('loading_wd');
	$serverAllowedImageFormatsJson = collect(getServerAllowedImageFormats())->toJson();
@endphp
@if (!empty($fields))
	@foreach($fields as $field)
		@php
			$modelFieldId = data_get($field, 'id');
			$modelFieldType = data_get($field, 'type');
			$modelDefaultValue = data_get($field, 'default_value');
			
			// Fields parameters
			$fieldId = 'cf.' . $modelFieldId;
			$fieldName = 'cf[' . $modelFieldId . ']';
			$fieldOld = 'cf.' . $modelFieldId;
			
			// Errors & Required CSS
			$requiredClass = (data_get($field, 'required') == 1) ? 'required' : '';
			$errorClass = (isset($errors[$fieldOld])) ? ' is-invalid' : '';
			
			// Get the default value
			$defaultValue = $oldInput[$modelFieldId] ?? $modelDefaultValue;
			
			// Get field other attributes
			$fieldOptions = data_get($field, 'options') ?? [];
			$fieldOptions = is_array($fieldOptions) ? $fieldOptions : [];
		@endphp
		
		@if ($modelFieldType == 'checkbox')
			
			{{-- checkbox --}}
			@include('helpers.forms.fields.checkbox', [
				'label'          => data_get($field, 'name') . '-aa',
				'id'             => $fieldId,
				'name'           => $fieldName,
				'required'       => (data_get($field, 'required') == 1),
				'value'          => $defaultValue,
				'hint'           => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
		
		@elseif ($modelFieldType == 'checkbox_multiple')
			
			@if (!empty($fieldOptions))
				{{-- checkbox_multiple --}}
				@php
					$checkedBoxes = $modelDefaultValue;
					
					$checkboxes = collect($fieldOptions)
						->mapWithKeys(function($option) use (
							$fieldId, $fieldName, $checkedBoxes, $oldInput, $modelFieldId
						) {
							$optionId = $option['id'] ?? null;
							$optionValue = $option['value'] ?? null;
							
							// Get the checkbox attributes value
							$checkboxLabel = $optionValue;
							$checkboxId = $fieldId . '_' . $optionId;
							$checkboxName = $fieldName . '[' . $optionId . ']';
							$checkboxValue = is_array($checkedBoxes) ? ($checkedBoxes[$optionId]['id'] ?? null) : $checkedBoxes;
							// $checkboxValue = is_array($checkedBoxes) ? ($checkedBoxes[$optionId] ?? $optionValue) : $optionValue;
							$checkboxValue = $oldInput[$modelFieldId][$optionId] ?? $checkboxValue;
							
							return [
								$optionId => [
									'label'     => $checkboxLabel,
									'id'        => $checkboxId,
									'name'      => $checkboxName,
									'value'     => $checkboxValue,
									'isChecked' => ($checkboxValue == $optionId),
								],
							];
						})->toArray();
					
					$defaultValue = collect($defaultValue)->pluck('id', 'id')->toArray();
				@endphp
				@include('helpers.forms.fields.checklist', [
					'label'          => data_get($field, 'name'),
					'id'             => $fieldId,
					'name'           => $fieldName,
					'required'       => (data_get($field, 'required') == 1),
					'checkboxes'     => $checkboxes,
					'col'            => 12,
					'value'          => $defaultValue,
					'hint'           => data_get($field, 'help'),
					'isInvalidClass' => $errorClass,
				])
			@endif
			
		@elseif ($modelFieldType == 'file')
			
			{{-- file --}}
			@php
				$fileHint =  data_get($field, 'help')
					. '<br>' . t('file_types', ['file_types' => getAllowedFileFormatsHint()], 'global', $langCode);
			@endphp
			@include('helpers.forms.fields.fileinput', [
				'label'    => data_get($field, 'name'),
				'id'       => $fieldId,
				'name'     => $fieldName,
				'required' => (data_get($field, 'required') == 1),
				'value'    => [
					'key'  => 1,
					'path' => $modelDefaultValue,
					'url'  => privateFileUrl($modelDefaultValue, null),
				],
				'hint'           => $fileHint,
				'downloadable'   => true,
				'isInvalidClass' => $errorClass,
			])
		
		@elseif ($modelFieldType == 'radio')
			
			@if (!empty($fieldOptions))
				{{-- radio --}}
				@include('helpers.forms.fields.radio', [
					'label'           => data_get($field, 'name'),
					'name'            => $fieldName,
					'inline'          => true,
					'required'        => (data_get($field, 'required') == 1),
					'options'         => $fieldOptions,
					'optionValueName' => 'id',
					'optionTextName'  => 'value',
					'value'           => $defaultValue,
					'hint'            => data_get($field, 'help'),
					'isInvalidClass'  => $errorClass,
				])
			@endif
		
		@elseif ($modelFieldType == 'select')
			
			{{-- select --}}
			@include('helpers.forms.fields.select2', [
				'label'           => data_get($field, 'name'),
				'id'              => $fieldId,
				'name'            => $fieldName,
				'required'        => (data_get($field, 'required') == 1),
				'placeholder'     => t('Select', [], 'global', $langCode),
				'options'         => $fieldOptions,
				'optionValueName' => 'id',
				'optionTextName'  => 'value',
				'value'           => $defaultValue,
				'hint'            => data_get($field, 'help'),
				'isInvalidClass'  => $errorClass,
			])
		
		@elseif ($modelFieldType == 'textarea')
			
			{{-- textarea --}}
			@php
				$textAreaAttributes = ['rows' => 10];
				$fieldMax = (int)data_get($field, 'max');
				if (!empty($fieldMax)) {
					$textAreaAttributes['maxlength'] = $fieldMax;
				}
			@endphp
			@include('helpers.forms.fields.textarea', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'attributes'  => $textAreaAttributes,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
		
		@elseif ($modelFieldType == 'url')
			
			{{-- url --}}
			@include('helpers.forms.fields.url', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
		
		@elseif ($modelFieldType == 'number')
			
			{{-- number --}}
			@php
				$numberAttributes = [];
				$fieldMax = (int)data_get($field, 'max');
				if (!empty($fieldMax)) {
					$numberAttributes['max'] = $fieldMax;
				}
			@endphp
			@include('helpers.forms.fields.number', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'attributes'  => $numberAttributes,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
		
		@elseif ($modelFieldType == 'date')
			
			{{-- date --}}
			@include('helpers.forms.fields.daterangepicker-date', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
			
		@elseif ($modelFieldType == 'date_time')
			
			{{-- date_time --}}
			@include('helpers.forms.fields.daterangepicker-datetime', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
			
		@elseif ($modelFieldType == 'date_range')
			
			{{-- date_range --}}
			@include('helpers.forms.fields.daterangepicker-daterange', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
			
		@else
			
			{{-- text --}}
			@php
				$textAttributes = [];
				$fieldMax = (int)data_get($field, 'max');
				if (!empty($fieldMax)) {
					$textAttributes['maxlength'] = $fieldMax;
				}
			@endphp
			@include('helpers.forms.fields.text', [
				'label'       => data_get($field, 'name'),
				'id'          => $fieldId,
				'name'        => $fieldName,
				'placeholder' => data_get($field, 'name'),
				'required'    => (data_get($field, 'required') == 1),
				'value'       => $defaultValue,
				'attributes'  => $textAttributes,
				'hint'        => data_get($field, 'help'),
				'isInvalidClass' => $errorClass,
			])
			
		@endif
	@endforeach
@endif

{{--
	During XHR requests assets stack are not pushed. ---
	So, here below the JS code linked to the fields loaded using XHR request.
	Note: The JS plugins files are loaded in the parent views:
	e.g. resources/views/front/post/createOrEdit/inc/form-assets.blade.php
--}}
<script>
	{{-- Select2 --}}
	onDocumentReady((event) => {
		const lang = '{{ $langCode }}';
		const dir = '{{ $langDirection }}';
		const theme = 'bootstrap-5';
		const select2Els = $('#cfContainer .select2-from-array');
		const largeSelect2Els = $('#cfContainer .select2-from-large-array');
		
		const options = {
			lang: lang, {{-- No effect, use the "options.language" property --}}
			dir: dir,
			width: '100%',
			dropdownAutoWidth: 'true',
			minimumResultsForSearch: Infinity, /* Hiding the search box */
		};
		
		if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
			options.language = langLayout.select2;
		}
		if (typeof theme !== 'undefined') {
			options.theme = theme;
		}
		
		/* Non-searchable select boxes */
		if (select2Els.length) {
			select2Els.each((index, element) => {
				if (!$(element).hasClass('select2-hidden-accessible')) {
					if (typeof theme !== 'undefined') {
						if (theme === 'bootstrap-5') {
							let widthOption = $(element).hasClass('w-100') ? '100%' : 'style';
							options.width = $(element).data('width') ? $(element).data('width') : widthOption;
							options.placeholder = $(element).data('placeholder');
						}
					}
					
					$(element).select2(options);
					
					/* Indicate that the value of this field has changed */
					$(element).on('select2:select', (e) => {
						element.dispatchEvent(new Event('input', {bubbles: true}));
					});
				}
			});
		}
		
		/* Searchable select boxes */
		if (largeSelect2Els.length) {
			largeSelect2Els.each((index, element) => {
				if (!$(element).hasClass('select2-hidden-accessible')) {
					if (typeof theme !== 'undefined') {
						if (theme === 'bootstrap-5') {
							const widthOption = $(element).hasClass('w-100') ? '100%' : 'style';
							const width = $(element).data('width');
							options.width = width ? width : widthOption;
							options.placeholder = $(element).data('placeholder');
						}
					}
					
					delete options.minimumResultsForSearch;
					$(element).select2(options);
					
					/* Indicate that the value of this field has changed */
					$(element).on('select2:select', (e) => {
						element.dispatchEvent(new Event('input', {bubbles: true}));
					});
				}
			});
		}
	});
	
	{{-- File Input --}}
	onDocumentReady((event) => {
		var fiOptions = {};
		fiOptions.theme = '{{ $fiTheme }}';
		fiOptions.language = '{{ $langCode }}';
		fiOptions.rtl = {{ $isRtl }};
		fiOptions.showUpload = false;
		fiOptions.showRemove = false;
		fiOptions.showCancel = true;
		fiOptions.showPreview = false;
		fiOptions.dropZoneEnabled = false;
		fiOptions.browseOnZoneClick = false;
		
		const $fileInputEl = $('#cfContainer .file');
		$fileInputEl.fileinput(fiOptions);
	});
	
	{{-- Date Picker --}}
	onDocumentReady((event) => {
		/*
		 * Custom Fields Date Picker
		 * https://www.daterangepicker.com/#options
		 */
		{{-- Single Date --}}
		let dateEl = $('#cfContainer .be-select-date');
		dateEl.daterangepicker({
			autoUpdateInput: false,
			autoApply: true,
			showDropdowns: true,
			minYear: parseInt(moment().format('YYYY')) - 100,
			maxYear: parseInt(moment().format('YYYY')) + 20,
			locale: {
				format: '{{ t('datepicker_format') }}',
				applyLabel: "{{ t('datepicker_applyLabel') }}",
				cancelLabel: "{{ t('datepicker_cancelLabel') }}",
				fromLabel: "{{ t('datepicker_fromLabel') }}",
				toLabel: "{{ t('datepicker_toLabel') }}",
				customRangeLabel: "{{ t('datepicker_customRangeLabel') }}",
				weekLabel: "{{ t('datepicker_weekLabel') }}",
				daysOfWeek: [
					"{{ t('datepicker_sunday') }}",
					"{{ t('datepicker_monday') }}",
					"{{ t('datepicker_tuesday') }}",
					"{{ t('datepicker_wednesday') }}",
					"{{ t('datepicker_thursday') }}",
					"{{ t('datepicker_friday') }}",
					"{{ t('datepicker_saturday') }}"
				],
				monthNames: [
					"{{ t('January') }}",
					"{{ t('February') }}",
					"{{ t('March') }}",
					"{{ t('April') }}",
					"{{ t('May') }}",
					"{{ t('June') }}",
					"{{ t('July') }}",
					"{{ t('August') }}",
					"{{ t('September') }}",
					"{{ t('October') }}",
					"{{ t('November') }}",
					"{{ t('December') }}"
				],
				firstDay: 1
			},
			singleDatePicker: true,
			startDate: moment().format('{{ t('datepicker_format') }}')
		});
		dateEl.on('apply.daterangepicker', function(ev, picker) {
			$(this).val(picker.startDate.format('{{ t('datepicker_format') }}'));
		});
		
		{{-- Single Date (with Time) --}}
		let dateTimeEl = $('#cfContainer .be-select-datetime');
		dateTimeEl.daterangepicker({
			autoUpdateInput: false,
			autoApply: true,
			showDropdowns: false,
			minYear: parseInt(moment().format('YYYY')) - 100,
			maxYear: parseInt(moment().format('YYYY')) + 20,
			locale: {
				format: '{{ t('datepicker_format_datetime') }}',
				applyLabel: "{{ t('datepicker_applyLabel') }}",
				cancelLabel: "{{ t('datepicker_cancelLabel') }}",
				fromLabel: "{{ t('datepicker_fromLabel') }}",
				toLabel: "{{ t('datepicker_toLabel') }}",
				customRangeLabel: "{{ t('datepicker_customRangeLabel') }}",
				weekLabel: "{{ t('datepicker_weekLabel') }}",
				daysOfWeek: [
					"{{ t('datepicker_sunday') }}",
					"{{ t('datepicker_monday') }}",
					"{{ t('datepicker_tuesday') }}",
					"{{ t('datepicker_wednesday') }}",
					"{{ t('datepicker_thursday') }}",
					"{{ t('datepicker_friday') }}",
					"{{ t('datepicker_saturday') }}"
				],
				monthNames: [
					"{{ t('January') }}",
					"{{ t('February') }}",
					"{{ t('March') }}",
					"{{ t('April') }}",
					"{{ t('May') }}",
					"{{ t('June') }}",
					"{{ t('July') }}",
					"{{ t('August') }}",
					"{{ t('September') }}",
					"{{ t('October') }}",
					"{{ t('November') }}",
					"{{ t('December') }}"
				],
				firstDay: 1
			},
			singleDatePicker: true,
			timePicker: true,
			timePicker24Hour: true,
			startDate: moment().format('{{ t('datepicker_format_datetime') }}')
		});
		dateTimeEl.on('apply.daterangepicker', function(ev, picker) {
			$(this).val(picker.startDate.format('{{ t('datepicker_format_datetime') }}'));
		});
		
		{{-- Date Range --}}
		let dateRangeEl = $('#cfContainer .be-select-daterange');
		dateRangeEl.daterangepicker({
			autoUpdateInput: false,
			autoApply: true,
			showDropdowns: false,
			minYear: parseInt(moment().format('YYYY')) - 100,
			maxYear: parseInt(moment().format('YYYY')) + 20,
			locale: {
				format: '{{ t('datepicker_format') }}',
				applyLabel: "{{ t('datepicker_applyLabel') }}",
				cancelLabel: "{{ t('datepicker_cancelLabel') }}",
				fromLabel: "{{ t('datepicker_fromLabel') }}",
				toLabel: "{{ t('datepicker_toLabel') }}",
				customRangeLabel: "{{ t('datepicker_customRangeLabel') }}",
				weekLabel: "{{ t('datepicker_weekLabel') }}",
				daysOfWeek: [
					"{{ t('datepicker_sunday') }}",
					"{{ t('datepicker_monday') }}",
					"{{ t('datepicker_tuesday') }}",
					"{{ t('datepicker_wednesday') }}",
					"{{ t('datepicker_thursday') }}",
					"{{ t('datepicker_friday') }}",
					"{{ t('datepicker_saturday') }}"
				],
				monthNames: [
					"{{ t('January') }}",
					"{{ t('February') }}",
					"{{ t('March') }}",
					"{{ t('April') }}",
					"{{ t('May') }}",
					"{{ t('June') }}",
					"{{ t('July') }}",
					"{{ t('August') }}",
					"{{ t('September') }}",
					"{{ t('October') }}",
					"{{ t('November') }}",
					"{{ t('December') }}"
				],
				firstDay: 1
			},
			startDate: moment().format('{{ t('datepicker_format') }}'),
			endDate: moment().add(1, 'days').format('{{ t('datepicker_format') }}')
		});
		dateRangeEl.on('apply.daterangepicker', function(ev, picker) {
			$(this).val(picker.startDate.format('{{ t('datepicker_format') }}') + ' - ' + picker.endDate.format('{{ t('datepicker_format') }}'));
		});
	});
</script>
