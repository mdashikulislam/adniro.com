{{-- view field --}}
@php
	$viewName = 'view';
	$view ??= '';
@endphp
@if (!empty($view) && view()->exists($view))
	<div @include('helpers.forms.attributes.field-wrapper')>
		@include($view)
	</div>
	@include('helpers.forms.partials.newline')
@endif
