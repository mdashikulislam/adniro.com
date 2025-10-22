{{-- checklist --}}
@php
    use Illuminate\Database\Eloquent\Model;
	use Illuminate\Support\Collection;
	
	$field ??= [];
	$entityModel = $field['model'] ?? $xPanel->getModel();
	$entries = $entityModel::all();
	
	$name = $field['name'] ?? '';
	$label = $field['label'] ?? '';
	$hint = $field['hint'] ?? null;
	$isRequired = (bool)($field['required'] ?? false);
	$attribute = $field['attribute'] ?? '';
	$default = (int)($field['default'] ?? 0);
	$value = $field['value'] ?? [];
	
	$dotSepName = arrayFieldToDotNotation($name);
	$value = old($dotSepName, $value);
	$value = ($value instanceof Collection || is_array($value)) ? $value : collect();
@endphp
<div @include('admin.panel.inc.field_wrapper_attributes') >
    <label class="fw-bolder">
        {!! $label !!}
        @if ($isRequired)
            <span class="text-danger">*</span>
        @endif
    </label>
    @include('admin.panel.fields.inc.translatable_icon')
    
    <div class="row">
        @foreach ($entries as $entry)
            @php
                /** @var Model $entry */
                $formattedValue = ($value instanceof Collection)
                    ? $value->pluck($entry->getKeyName(), $entry->getKeyName())->toArray()
                    : $value
            @endphp
            <div class="col-sm-4">
                <div class="form-check">
                    <input
                            type="checkbox"
                            class="form-check-input"
                            name="{{ $name }}[]"
                            value="{{ $entry->getKey() }}"
                            @if (in_array($entry->getKey(), $formattedValue))
                                checked = "checked"
                            @endif
                    >
                    <label class="form-check-label">
                        {!! $entry->{$field['attribute']} !!}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- HINT --}}
    @if (!empty($hint))
        <div class="form-text mt-2">{!! $hint !!}</div>
    @endif
</div>
