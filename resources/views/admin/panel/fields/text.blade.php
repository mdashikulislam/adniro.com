{{-- text input --}}
@php
    $field ??= [];
	
	$label = $field['label'] ?? '';
	$name = $field['name'];
	$id = $field['id'] ?? null;
	$id = getFieldIdentifier(id: $id, name: $name);
	
	$isRequired = $field['required'] ?? false;
	$prefix = $field['prefix'] ?? null;
	$suffix = $field['suffix'] ?? null;
	$hint = $field['hint'] ?? null;
	$wrapper = $field['wrapper'] ?? [];
	
	$dotSepName = arrayFieldToDotNotation($name);
	
	$value = $field['value'] ?? $field['default'] ?? null;
	$value = old($dotSepName, $value);
@endphp
<div @include('admin.panel.inc.field_wrapper_attributes')>
    <label class="form-label fw-bolder">
        {!! $label !!}
        @if ($isRequired)
            <span class="text-danger">*</span>
        @endif
    </label>
    @include('admin.panel.fields.inc.translatable_icon')
    
    @if (!empty($prefix) || !empty($suffix)) <div class="input-group"> @endif
    @if (!empty($prefix)) <span class="input-group-text">{!! $prefix !!}</span> @endif
    <input
        type="text"
        name="{{ $name }}"
        value="{{ $value }}"
        @include('admin.panel.inc.field_attributes')
    >
    @if (!empty($suffix)) <span class="input-group-text">{!! $suffix !!}</span> @endif
    @if (!empty($prefix) || !empty($suffix)) </div> @endif
    
    {{-- HINT --}}
    @if (!empty($hint))
        <div class="form-text">{!! $hint !!}</div>
    @endif
</div>


{{-- FIELD EXTRA CSS  --}}
{{-- push things in the after_styles section --}}

    {{-- @push('crud_fields_styles')
        <!-- no styles -->
    @endpush --}}


{{-- FIELD EXTRA JS --}}
{{-- push things in the after_scripts section --}}

    {{-- @push('crud_fields_scripts')
        <!-- no scripts -->
    @endpush --}}


{{-- Note: you can use @if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields)) to only load some CSS/JS once, even though there are multiple instances of it --}}
