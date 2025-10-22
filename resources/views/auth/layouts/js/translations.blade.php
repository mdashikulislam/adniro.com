{{-- In your Blade template (e.g., layout.blade.php or wherever your scripts are included) --}}
<script>
	@php
		$translations = [
	        'hide_password' => trans('auth.hide_password'),
	        'show_password' => trans('auth.show_password'),
	        'hide'          => trans('auth.hide'),
	        'show'          => trans('auth.show'),
	        'verify'        => trans('auth.verify'),
	        'submitting'    => trans('auth.submitting'),
	    ];
	@endphp
	window.authTranslations = {!! json_encode($translations) !!};
</script>
