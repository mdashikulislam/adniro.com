{{-- checkbox --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'checkbox';
	$type = 'checkbox';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$switch ??= false;
	$reverse ??= false;
	$checkLabelClass ??= '';
	$checkLabelClass .= !empty($label) ? (!empty($checkLabelClass) ? ' fw-normal' : 'fw-normal') : '';
	$checkLabelClass = !empty($checkLabelClass) ? " $checkLabelClass" : '';
	$labelRightContent ??= null;
	$attributes ??= [];
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$isFieldChecked = str_ends_with($name, '_at') ? !empty($value) : ((int)$value === 1 && $value !== '0');
	
	$attrStr = '';
	$attrStr = (!empty($value) && $isFieldChecked) ? 'checked="checked"' : '';
	if (!empty($attributes)) {
		foreach ($attributes as $attribute => $value) {
			$value = ($attribute == 'class') ? "form-check-input $value" : $value;
			$attrStr .= !empty($attrStr) ? ' ' : '';
			$attrStr .= $attribute . '="' . $value . '"';
		}
	} else {
		$attrStr .= !empty($attrStr) ? ' ' : '';
		$attrStr .= 'class="form-check-input"';
	}
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@php
				$switchClass = $switch ? ' form-switch' : '';
				$reverseClass = $reverse ? ' form-check-reverse' : '';
				$horizontalClass = $isHorizontal ? ' mt-2' : '';
			@endphp
			@if (!empty($labelRightContent))
				<div class="row">
					<div class="col text-start">
						<div class="form-check{{ $switchClass . $reverseClass . $horizontalClass }}">
							<input type="hidden" name="{{ $name }}" value="0">
							<input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1"{!! $attrStr !!}>
							<label class="form-check-label{{ $checkLabelClass }}" for="{{ $id }}">
								{!! $label !!}
							</label>
							
							@include('helpers.forms.partials.hint')
							@include('helpers.forms.partials.validation')
						</div>
					</div>
					<div class="col-6 text-end">
						{!! $labelRightContent !!}
					</div>
				</div>
			@else
				<div class="form-check{{ $switchClass . $reverseClass . $horizontalClass }}">
					<input type="hidden" name="{{ $name }}" value="0">
					<input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1"{!! $attrStr !!}>
					<label class="form-check-label{{ $checkLabelClass }}" for="{{ $id }}">
						{!! $label !!}
					</label>
					
					@include('helpers.forms.partials.hint')
					@include('helpers.forms.partials.validation')
				</div>
			@endif
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
