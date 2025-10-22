{{-- number input --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'number';
	$type = 'number';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$prefix ??= null;
	$suffix ??= null;
	$required ??= false;
	$hint ??= null;
	
	$min ??= null;
	$max ??= null;
	$step ??= null;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$attrStr = is_numeric($min) ? ' min="' . $min . '"' : '';
	$attrStr .= is_numeric($max) ? ' max="' . $max . '"' : '';
	$attrStr .= is_numeric($step) ? ' step="' . $step . '"' : '';
	
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
			
			@if (!empty($prefix) || !empty($suffix))
				<div class="input-group {{ $isInvalidClass }}">
					@endif
					@if (!empty($prefix))
						<span class="input-group-text">{!! $prefix !!}</span>
					@endif
					<input
							type="number"
							name="{{ $name }}"
							id="{{ $name }}"
							value="{{ $value }}"
							@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
							{!! $attrStr !!}
							@include('helpers.forms.attributes.field')
					>
					@if (!empty($suffix))
						<span class="input-group-text">{!! $suffix !!}</span>
					@endif
					@if (!empty($prefix) || !empty($suffix))
				</div>
			@endif
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
