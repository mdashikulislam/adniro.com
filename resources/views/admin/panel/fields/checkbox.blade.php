{{-- checkbox --}}
@php
	$field ??= [];
	
	$name = $field['name'] ?? '';
	$label = $field['label'] ?? '';
	$hint = $field['hint'] ?? null;
	$attributes = $field['attributes'] ?? [];
	$isRequired = (bool)($field['required'] ?? false);
	$default = (int)($field['default'] ?? 0);
	$value = $field['value'] ?? $default;
	
	// $entry ??= null;
	// $value = $entry->{$name} ?? $value;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$value = old($dotSepName, $value);
	
	$isChecked = (str_ends_with($name, '_at')) ? !empty($value) : ((int)$value == 1 && $value !== '0');
@endphp
<div @include('admin.panel.inc.field_wrapper_attributes') >
	@include('admin.panel.fields.inc.translatable_icon')
    <div class="form-check" style="margin-top: 32px;">
		<input type="hidden" name="{{ $name }}" value="0">
		<input type="checkbox" value="1" name="{{ $name }}"
		@if ($isChecked)
		   checked="checked"
		@endif
	 
		@if (!empty($attributes))
			@foreach ($attributes as $attribute => $attrValue)
				@if ($attribute == 'class')
					{{ $attribute }}="form-check-input {{ $attrValue }}"
				@else
					{{ $attribute }}="{{ $attrValue }}"
				@endif
			@endforeach
		@else
			class="form-check-input"
		@endif
	>
		<label class="form-check-label fw-bolder">
			{!! $field['label'] !!}
			@if ($isRequired)
				<span class="text-danger">*</span>
			@endif
		</label>
		
		{{-- HINT --}}
		@if (!empty($hint))
			<div class="form-text">{!! $hint !!}</div>
		@endif
    </div>
</div>
