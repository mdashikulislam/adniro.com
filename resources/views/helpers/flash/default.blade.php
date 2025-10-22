@php
	$supportedPresenters = ['bsmodal', 'bstoast', 'pnotify', 'sweetalert2'];
	$fallback = 'pnotify';
	
	$presenter = config('larapen.flash.default');
	$presenter = castToStringOrNull($presenter);
	$presenter = (!empty($presenter) && in_array($presenter, $supportedPresenters)) ? $presenter : $fallback;
	
	$presenterOptions = (array)config("larapen.flash.presenters.{$presenter}", []);
	$viewPath = "helpers.flash.presenters.{$presenter}";
@endphp
@if (view()->exists($viewPath))
	@include($viewPath, ['presenterOptions' => $presenterOptions])
@endif
