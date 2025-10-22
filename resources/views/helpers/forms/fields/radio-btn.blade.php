{{-- radio-btn --}}
@php
	use App\Helpers\Common\Arr;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'radio-btn';
	$type = 'radio';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$btnSizes = ['lg', 'sm'];
	$btnSize ??= null; // 'sm';
	$btnSize = (!empty($btnSize) && in_array($btnSize, $btnSizes)) ? " btn-{$btnSize}" : '';
	
	$btnVariants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link'];
	$btnVariant ??= null;
	$btnOutline ??= false;
	$outline = $btnOutline ? 'outline-' : '';
	$btnVariant = (!empty($btnVariant) && in_array($btnVariant, $btnVariants)) ? " btn-{$outline}{$btnVariant}" : '';
	
	$checkLabelClass ??= '';
	$checkLabelClass .= !empty($label) ? (!empty($checkLabelClass) ? ' fw-normal' : 'fw-normal') : '';
	$checkLabelClass = !empty($checkLabelClass) ? " $checkLabelClass" : '';
	$options ??= [];
	$optionValueName ??= 'value';
	$optionTextName ??= 'text';
	$inline ??= false;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
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
					$optionPointer = 0
				@endphp
				<div class="{{ $isHorizontal ? ' mt-2' : '' }}">
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
						
						<input
								type="radio"
								id="{{ $radioId }}"
								name="{{ $name }}"
								value="{{ $optionValue }}"
								autocomplete="off"{!! $optionAttrsStr !!}
								@checked($optionValue == $value)
								@include('helpers.forms.attributes.field')
						>
						<label class="btn{{ $btnVariant . $btnSize . $checkLabelClass }}" for="{{ $radioId }}">
							{!! $optionText !!}
						</label>
						
					@endforeach
				</div>
			@endif
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
