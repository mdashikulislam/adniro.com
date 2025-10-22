@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName ??= null;
	$name ??= '';
	$label ??= null;
	$labelClass ??= '';
	$labelRightContent ??= null;
	$required ??= false;
	
	$labelClass = !empty($labelClass) ? " $labelClass" : '';
	
	$class = $isHorizontal ? "$colLabel col-form-label d-flex justify-content-end" : 'form-label';
	$class = $class . $labelClass;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	// Handle error class
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	$class .= !empty($class) ? ' ' . $isInvalidClass : $isInvalidClass;
	
	// Handle label
	if (in_array($viewName, ['checkbox', 'checkbox-switch'])) {
		$label = null;
	}
@endphp
@if (!empty($label))
	@if (!empty($labelRightContent))
		@if ($isHorizontal)
			<label class="{{ $class }}" for="{{ $id }}">
				{!! $label !!}@if ($required)<span class="text-danger ms-1">*</span>@endif {!! "($labelRightContent)" !!}
			</label>
		@else
			<div class="row">
				<label class="{{ $class }} col-6 text-start" for="{{ $id }}">
					{!! $label !!}@if ($required)<span class="text-danger ms-1">*</span>@endif
				</label>
				<div class="col-6 text-end">
					{!! $labelRightContent !!}
				</div>
			</div>
		@endif
	@else
		<label class="{{ $class }}" for="{{ $id }}">
			{!! $label !!}@if ($required)<span class="text-danger ms-1">*</span>@endif
		</label>
	@endif
@else
	@if ($isHorizontal)
		<label class="{{ $class }}"></label>
	@endif
@endif
