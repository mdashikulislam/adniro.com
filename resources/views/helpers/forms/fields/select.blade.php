{{-- select --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'select';
	$type = 'select';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	
	$options ??= [];
	$optionValueName ??= 'value';
	$optionTextName ??= 'text';
	$allowsMultiple ??= false;
	
	$name = $allowsMultiple ? $name . '[]' : $name;
	$multipleAttr = $allowsMultiple ? ' multiple' : '';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<select
					id="{{ $id }}"
					name="{{ $name }}"
					@include('helpers.forms.attributes.field')
					{!! $multipleAttr !!}
			>
				@if (!empty($placeholder))
					<option value="">{{ $placeholder }}</option>
				@endif
				@if (!empty($options) && is_array($options))
					@foreach ($options as $key => $option)
						@php
							$optionValue = $option[$optionValueName] ?? null;
							$optionText = $option[$optionTextName] ?? null;
							$optionAttrs = $option['attributes'] ?? [];
							$optionAttrsStr = \App\Helpers\Common\Arr::toAttributes($optionAttrs);
							$optionAttrsStr = !empty($optionAttrsStr) ? ' ' . $optionAttrsStr : '';
							
							$isSelected = ($optionValue == $value);
						@endphp
						<option value="{{ $optionValue }}"{!! $optionAttrsStr !!} @selected($isSelected)>
							{{ $optionText }}
						</option>
					@endforeach
				@endif
			</select>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
