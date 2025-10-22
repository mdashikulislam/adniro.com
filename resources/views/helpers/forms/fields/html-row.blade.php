{{-- Used for heading, separators, etc --}}
@php
	$viewName = 'custom-html';
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	{!! $value !!}
</div>
@include('helpers.forms.partials.newline')
