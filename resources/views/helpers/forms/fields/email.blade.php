{{-- text input --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'email';
	$type = 'email';
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
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
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
							type="email"
							name="{{ $name }}"
							value="{{ $value }}"
							@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
							@include('helpers.forms.attributes.field')
					>
					@if (!empty($suffix))
						<span class="input-group-text">{!! $suffix !!}</span>>
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
