@php
	$postInput ??= [];
	$post ??= [];
	$admin ??= [];
	
	$isSingleStepCreateForm = (isSingleStepFormEnabled() && request()->segment(1) == 'create');
	$isSingleStepEditForm = (isSingleStepFormEnabled() && request()->segment(1) == 'edit');
	
	$langCode ??= null;
	$langCode = $langCode ?? config('app.locale', session('langCode'));
	$langDirection = config('lang.direction');
	$isRtl = $pluginOptions['rtl'] ?? (($langDirection == 'rtl') ? 'true' : 'false');
	
	$picturesLimit ??= 0;
	$picturesLimit = is_numeric($picturesLimit) ? $picturesLimit : 0;
	$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : 1;
	
	$pictures = [];
	if ($isSingleStepEditForm) {
		$pictures = data_get($post, 'pictures', []);
		$pictures = collect($pictures)->slice(0, (int)$picturesLimit)->all();
	}
	
	$postId = data_get($post, 'id') ?? '';
	$postTypeId = data_get($post, 'post_type_id') ?? data_get($postInput, 'post_type_id', 0);
	$countryCode = data_get($post, 'country_code') ?? data_get($postInput, 'country_code', config('country.code', 0));
	
	$adminType = config('country.admin_type', 0);
	
	$selectedAdminCode = data_get($postInput, 'admin_code', 0);
	$selectedAdminCode = data_get($admin, 'code', $selectedAdminCode);
	
	$cityId = data_get($postInput, 'city_id');
	$cityId = data_get($post, 'city_id', $cityId);
	
	$s2Themes = [
		'bootstrap5' => 'bootstrap-5',
		'bootstrap4' => 'bootstrap4',
		'bootstrap3' => 'bootstrap',
	];
	$s2Theme = config('larapen.core.select2.theme', 'bootstrap5');
	$s2ThemeKey = $s2Themes[$s2Theme] ?? null;
	
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
	$fiFileLoadingMessage ??= t('loading_wd');
	$serverAllowedImageFormatsJson = collect(getServerAllowedImageFormats())->toJson();
	
	$errors ??= getEmptyViewErrors();
	$errors = ($errors instanceof \Illuminate\Support\Collection) ? $errors : collect($errors->toArray());
	$errorsJson = addslashes($errors->toJson());
	
	$cfOldInput = data_get($postInput, 'cf');
	$cfOldInput = session()->getOldInput('cf', $cfOldInput);
	$cfOldInputJson = addslashes(collect($cfOldInput)->toJson());
@endphp
@section('modal_location')
	@include('front.layouts.partials.modal.location')
@endsection

{{-- For Custom Fields --}}
@pushonce("select2_assets_styles")
	<link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css"/>
	@if ($s2Theme == 'bootstrap5')
		<link href="{{ asset('assets/plugins/select2-bootstrap5-theme/1.3.0/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" type="text/css"/>
		@if ($isRtl == 'true')
			<link href="{{ asset('assets/plugins/select2-bootstrap5-theme/1.3.0/select2-bootstrap-5-theme.rtl.min.css') }}" rel="stylesheet" type="text/css"/>
		@endif
	@elseif ($s2Theme == 'bootstrap4')
		<link href="{{ asset('assets/plugins/select2-bootstrap4-theme/1.5.2/select2-bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
	@elseif ($s2Theme == 'bootstrap3')
		<link href="{{ asset('assets/plugins/select2-bootstrap3-theme/0.1.0-beta.10/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
	@else
		<link href="{{ asset('assets/plugins/select2/css/custom.css') }}" rel="stylesheet" type="text/css"/>
	@endif
@endpushonce
@pushonce("select2_assets_scripts")
	<script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
	@php
		$localeFilesBasePath = 'assets/plugins/select2/js/i18n/';
		$localeFilesFullPath = public_path($localeFilesBasePath);
		
		$foundLocale = '';
		if (file_exists($localeFilesFullPath . getLangTag($langCode) . '.js')) {
			$foundLocale = getLangTag($langCode);
		}
		if (empty($foundLocale)) {
			if (file_exists($localeFilesFullPath . strtolower($langCode) . '.js')) {
				$foundLocale = strtolower($langCode);
			}
		}
		if (empty($foundLocale)) {
			$foundLocale = 'en';
		}
	@endphp
	@if ($foundLocale != 'en')
		<script src="{{ asset($localeFilesBasePath . $foundLocale . '.js') }}"></script>
	@endif
@endpushonce
@pushonce("fileinput_assets_styles")
	<link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput.min.css') }}" rel="stylesheet">
	@if ($isRtl == 'true')
		<link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput-rtl.min.css') }}" rel="stylesheet">
	@endif
	@if (str_starts_with($fiTheme, 'explorer'))
		<link href="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.min.css') }}" rel="stylesheet">
	@endif
	<style>
		.krajee-default.file-preview-frame:hover:not(.file-preview-error) {
			box-shadow: 0 0 5px 0 #666666;
		}
		.file-loading:before {
			content: " {{ $fiFileLoadingMessage }}";
		}
	</style>
@endpushonce
@pushonce("fileinput_assets_scripts")
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/plugins/sortable.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/fileinput.min.js') }}" type="text/javascript"></script>
	@if (file_exists(public_path('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.js')))
		<script src="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.js') }}" type="text/javascript"></script>
	@endif
	<script src="{{ url('common/js/fileinput/locales/' . $langCode . '.js') }}" type="text/javascript"></script>
@endpushonce
@pushonce("momentjs_assets_scripts")
	<script src="{{ url('assets/plugins/momentjs/2.30.1/moment.min.js') }}" type="text/javascript"></script>
	@php
		$localeFilesBasePath = 'assets/plugins/momentjs/2.30.1/locale/';
		$localeFilesFullPath = public_path($localeFilesBasePath);
		
		$foundLocale = '';
		if (file_exists($localeFilesFullPath . getLangTag($langCode) . '.js')) {
			$foundLocale = getLangTag($langCode);
		}
		if (empty($foundLocale)) {
			if (file_exists($localeFilesFullPath . strtolower($langCode) . '.js')) {
				$foundLocale = strtolower($langCode);
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
@endpushonce
@pushonce("daterangepicker_date_assets_scripts")
	<script src="{{ url('assets/plugins/daterangepicker/3.1/daterangepicker.js') }}" type="text/javascript"></script>
@endpushonce

@push('before_helpers_scripts_stack')
	@include('front.common.js.payment-scripts')
	
	<script>
		/* Translation */
		var lang = {
			'select': {
				'country': "{{ t('select_a_country') }}",
				'admin': "{{ t('select_a_location') }}",
				'city': "{{ t('select_a_city') }}"
			},
			'price': "{{ t('price') }}",
			'salary': "{{ t('Salary') }}",
			'nextStepBtnLabel': {
				'next': "{{ t('Next') }}",
				'submit': "{{ t('Update') }}"
			}
		};
		
		var stepParam = 0;
		
		/* Category */
		var categoryWasSelected = false;
		@if ($errors->isNotEmpty() || !empty($postId))
			categoryWasSelected = true;
		@endif
		/* Custom Fields */
		var errors = '{!! $errorsJson !!}';
		var oldInput = '{!! $cfOldInputJson !!}';
		var postId = '{{ $postId }}';
		
		/* Permanent Posts */
		var permanentPostsEnabled = '{{ config('settings.listing_form.permanent_listings_enabled', 0) }}';
		var postTypeId = '{{ old('post_type_id', $postTypeId) }}';
		
		/* Locations */
		var countryCode = '{{ old('country_code', $countryCode) }}';
		var adminType = '{{ $adminType }}';
		var selectedAdminCode = '{{ old('admin_code', $selectedAdminCode) }}';
		var cityId = '{{ old('city_id', $cityId) }}';
		
		/* Packages */
		var packageIsEnabled = false;
		@if (isset($packages, $paymentMethods) && $packages->count() > 0 && $paymentMethods->count() > 0)
			packageIsEnabled = true;
		@endif
	</script>
	
	<script src="{{ url('assets/js/app/d.modal.category.js') . vTime() }}"></script>
@endpush

@push('after_helpers_scripts_stack')
	<script>
		var select2Lang = '{{ $foundLocale }}';
		var select2Dir = '{{ $langDirection }}';
		var select2Theme = {!! !empty($s2ThemeKey) ? "'{$s2ThemeKey}'" : 'undefined' !!};
	</script>
	@if (config('settings.listing_form.city_selection') == 'select')
		<script src="{{ url('assets/js/app/d.select.location.js') . vTime() }}"></script>
	@else
		<script src="{{ url('assets/js/app/browse.locations.js') . vTime() }}"></script>
		<script src="{{ url('assets/js/app/d.modal.location.js') . vTime() }}"></script>
	@endif
@endpush
