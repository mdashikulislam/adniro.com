{{-- radio --}}
@php
	use App\Helpers\Common\Arr;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'radio';
	$type = 'radio';
	$label ??= null;
	$id ??= null;
	$protectedId ??= false;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$reverse ??= false;
	$checkLabelClass ??= '';
	$checkLabelClass .= !empty($label) ? (!empty($checkLabelClass) ? ' fw-normal' : 'fw-normal') : '';
	$checkLabelClass = !empty($checkLabelClass) ? " $checkLabelClass" : '';
	$options ??= [];
	$optionValueName ??= 'value';
	$optionTextName ??= 'text';
	$inline ??= false;
	
	$dotSepName = arrayFieldToDotNotation($name);
	if (!$protectedId) {
		$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	}
	
	$value = $value ?? ($default ?? null);
	$value = old($name, $value);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@if (!empty($options) && is_array($options))
				@if ($inline && !$isHorizontal)<br>@endif
				
				@php
					$reverseClass = $reverse ? ' form-check-reverse' : '';
					$inlineClass = ($inline && !$reverse) ? ' form-check-inline' : '';
					$optionPointer = 0
				@endphp
				@foreach ($options as $key => $option)
					@php
						$optionPointer++;
						
						$optionValue = $option[$optionValueName] ?? null;
						$optionText = $option[$optionTextName] ?? null;
						$optionAttrs = $option['attributes'] ?? [];
						$optionAttrsStr = Arr::toAttributes($optionAttrs);
						$optionAttrsStr = !empty($optionAttrsStr) ? ' ' . $optionAttrsStr : '';
						
						$radioId = $id . $optionValue;
					@endphp
					
					<div class="form-check{{ $inlineClass . $reverseClass }}{{ $isHorizontal ? ' mt-2' : '' }}">
						<input
								type="radio"
								id="{{ $radioId }}"
								name="{{ $name }}"
								value="{{ $optionValue }}"{!! $optionAttrsStr !!}
								@checked($optionValue == $value)
								@include('helpers.forms.attributes.field')
						>
						<label class="form-check-label{{ $checkLabelClass }}" for="{{ $radioId }}">
							{!! $optionText !!}
						</label>
					</div>
				
				@endforeach
			@endif
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
