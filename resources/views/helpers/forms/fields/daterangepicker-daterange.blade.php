{{-- daterangepicker-daterange (Date Range Picker) --}}
{{-- https://www.daterangepicker.com --}}
{{-- https://github.com/dangrossman/daterangepicker --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'daterangepicker-daterange';
	$type = 'text';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$pluginOptions ??= [];
	
	$locale = $pluginOptions['locale'] ?? app()->getLocale();
	$minYear = (int)($pluginOptions['minYear'] ?? 100);
	$maxYear = (int)($pluginOptions['maxYear'] ?? 20);
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($name, $value);
	
	// If the column has been cast to Carbon or Date (using attribute casting),
	// Get the value as a date string
	$value = ($value instanceof \Carbon\Carbon) ? $value->format('Y-m-d H:i:s') : $value;
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'be-select-daterange');
	
	$hasInputGroup = (!empty($prefix) || !empty($suffix));
	
	// Handle error class for "input-group"
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@if ($hasInputGroup)
				<div class="input-group {{ $isInvalidClass }}">
					@endif
					@if (!empty($prefix))
						<span class="input-group-text">{!! $prefix !!}</span>
					@endif
					<input
							id="{{ $id }}"
							name="{{ $name }}"
							type="text"
							value="{{ $value }}"
							@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
							autocomplete="off"
							@include('helpers.forms.attributes.field')
					>
					@if (!empty($suffix))
						<span class="input-group-text">{!! $suffix !!}</span>
					@endif
					@if ($hasInputGroup)
				</div>
			@endif
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
	$pluginBasePath = 'assets/plugins/daterangepicker/3.1/';
	$momentJsBasePath = 'assets/plugins/momentjs/2.18.1/';
	$momentJsBasePath = 'assets/plugins/momentjs/2.30.1/'; // locale
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("jquery_assets_scripts")
	{{--<script src="{{ url('assets/plugins/jquery/3.3.1/jquery.min.js') }}" type="text/javascript"></script>--}}
@endpushonce

@pushonce("momentjs_assets_scripts")
	<script src="{{ url($momentJsBasePath . 'moment.min.js') }}" type="text/javascript"></script>
	@php
		$localeFilesBasePath = $momentJsBasePath . 'locale/';
		$localeFilesFullPath = public_path($localeFilesBasePath);
		
		$foundLocale = '';
		if (file_exists($localeFilesFullPath . getLangTag($locale) . '.js')) {
			$foundLocale = getLangTag($locale);
		}
		if (empty($foundLocale)) {
			if (file_exists($localeFilesFullPath . strtolower($locale) . '.js')) {
				$foundLocale = strtolower($locale);
			}
		}
		if (empty($foundLocale)) {
			$foundLocale = 'en';
		}
	@endphp
	@if ($foundLocale != 'en')
		<script charset="UTF-8" src="{{ asset($localeFilesBasePath . $foundLocale . '.min.js') }}"></script>
	@endif
@endpushonce

@pushonce("daterangepicker_date_assets_styles")
	<link href="{{ url('assets/plugins/daterangepicker/3.1/daterangepicker.css') }}" rel="stylesheet">
	<link href="{{ url('assets/plugins/daterangepicker/3.1/daterangepicker-dark.css') }}" rel="stylesheet">
@endpushonce

@pushonce("daterangepicker_date_assets_scripts")
	<script src="{{ url('assets/plugins/daterangepicker/3.1/daterangepicker.js') }}" type="text/javascript"></script>
@endpushonce

{{-- include field specific assets code --}}
@pushonce("{$viewName}_helper_styles")
@endpushonce

@pushonce("{$viewName}_helper_scripts")
	<script>
		onDocumentReady((event) => {
			{{-- Date Range --}}
			const dateRangeEl = $('.be-select-daterange');
			
			const minYear = {{ $minYear }};
			const maxYear = {{ $maxYear }};
			const datepickerFormat = '{{ t('datepicker_format') }}';
			const options = {
				autoUpdateInput: false,
				autoApply: true,
				showDropdowns: false,
				minYear: parseInt(moment().format('YYYY')) - minYear,
				maxYear: parseInt(moment().format('YYYY')) + maxYear,
				startDate: moment().format(datepickerFormat),
				endDate: moment().add(1, 'days').format(datepickerFormat),
				locale: {
					format: datepickerFormat,
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
				}
			};
			
			dateRangeEl.daterangepicker(options);
			
			dateRangeEl.on('apply.daterangepicker', function(e, picker) {
				$(this).val(picker.startDate.format(datepickerFormat) + ' - ' + picker.endDate.format(datepickerFormat));
			});
		});
	</script>
@endpushonce
