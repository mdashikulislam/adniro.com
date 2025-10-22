{{-- checklist_dependency --}}
@php
    $field ??= [];
	$id ??= null;
	
	$entityModel = $xPanel->getModel();
    
    // Short name for dependency fields
    $primaryDependency = $field['subfields']['primary'];
    $secondaryDependency = $field['subfields']['secondary'];
	
	// Get the field value
	$fieldValue = $field['value'] ?? [];
	
	$primaryDependencyValueCollection = $fieldValue[0] ?? collect();
	$primaryDependencyValue = $primaryDependencyValueCollection->pluck('id', 'id')->toArray();
	$primaryDependencyValue = old($primaryDependency['name'], $primaryDependencyValue);
	
	$secondaryDependencyValueCollection = $fieldValue[1] ?? collect();
	$secondaryDependencyValue = $secondaryDependencyValueCollection->pluck('id', 'id')->toArray();
	$secondaryDependencyValue = old($secondaryDependency['name'], $secondaryDependencyValue);
    
    // All items with relation
    $dependencies = $primaryDependency['model']::with($primaryDependency['entity_secondary'])->get();
    
    // Convert dependency array to simple matrix (primary id as key and array with secondaries id)
    $dependencyArray = [];
    foreach($dependencies as $primary){
        $dependencyArray[$primary->id] = [];
        foreach($primary->{$primaryDependency['entity_secondary']} as $secondary){
            $dependencyArray[$primary->id][] = $secondary->id;
        }
    }
    
    // For update form, get initial state of the entity
    if (!empty($id)) {
        // Get entity with relations for primary dependency
        $entityDependencies = $entityModel->with($primaryDependency['entity'])
            ->with($primaryDependency['entity'] . '.' . $primaryDependency['entity_secondary'])
            ->where('id', $id)
            ->first();
        
        $secondariesFromPrimary = [];
        
        // Convert relation in array
        $primaryArray = $entityDependencies->{$primaryDependency['entity']}->toArray();
		
        // Create secondary dependency from primary relation,
        // used to check what checkbox must be checked from second checklist
        $secondaryIds = [];
        if (!empty($primaryDependencyValue)) {
            foreach($primaryDependencyValue as $primaryItem) {
                foreach($dependencyArray[$primaryItem] as $secondItem) {
                    $secondaryIds[$secondItem] = $secondItem;
                }
            }
        } else {
			// Create dependencies from relation if not from validate error
            foreach($primaryArray as $primaryItem) {
                foreach($primaryItem[$secondaryDependency['entity']] as $secondItem) {
                    $secondaryIds[$secondItem['id']] = $secondItem['id'];
                }
            }
        }
    }
    
    // JSON encode of dependency matrix
    $dependencyJson = json_encode($dependencyArray);
@endphp
<div
        class="mb-3 col-md-12 checklist_dependency"
        data-entity="{{ $field['field_unique_name'] }}"
        @include('admin.panel.inc.field_wrapper_attributes')
>
    <label class="fw-bolder">{!! $field['label'] !!}</label>
    @include('admin.panel.fields.inc.translatable_icon')
    
    <script>
        var {{ $field['field_unique_name'] }} = {!! $dependencyJson !!};
    </script>
    
    <div class="row" >
        <div class="col-sm-12 mt-4 mb-2">
            <label class="fw-bolder">{!! $primaryDependency['label'] !!}</label>
        </div>
        
        <div class="hidden_fields_primary" data-name="{{ $primaryDependency['name'] }}">
            @if (!empty($primaryDependencyValue))
                @foreach($primaryDependencyValue as $item)
                    <input type="hidden" class="primary_hidden" name="{{ $primaryDependency['name'] }}[]" value="{{ $item }}">
                @endforeach
            @endif
        </div>
    
        @foreach ($primaryDependency['model']::all() as $connectedEntityEntry)
            @php
                $colSize = isset($primaryDependency['number_columns'])
                    ? intval(12/$primaryDependency['number_columns'])
                    : '4';
            @endphp
            <div class="col-sm-{{ $colSize }}">
                <div class="form-check">
                    <input type="checkbox"
                        data-id="{{ $connectedEntityEntry->id }}"
                        class='form-check-input primary_list'
                        @foreach ($primaryDependency as $attribute => $value)
                            @if (is_string($attribute) && $attribute != 'value')
                                @if ($attribute == 'name')
                                    {{ $attribute }}="{{ $value }}_show[]"
                                @else
                                    {{ $attribute }}="{{ $value }}"
                                @endif
                            @endif
                        @endforeach
                        value="{{ $connectedEntityEntry->id }}"
                        @if (
                            !empty($primaryDependencyValue)
                            && in_array($connectedEntityEntry->id, $primaryDependencyValue)
                        )
                            checked="checked"
                        @endif
                    >
                    <label class="form-check-label">
                        {{ $connectedEntityEntry->{$primaryDependency['attribute']} }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-sm-12 mt-4 mb-2">
            <label class="fw-bolder">{!! $secondaryDependency['label'] !!}</label>
        </div>
        
        <div class="hidden_fields_secondary" data-name="{{ $secondaryDependency['name'] }}">
            @if (!empty($secondaryDependencyValue))
                @foreach($secondaryDependencyValue as $item)
                    <input type="hidden" class="secondary_hidden" name="{{ $secondaryDependency['name'] }}[]" value="{{ $item }}">
                @endforeach
            @endif
        </div>
        
        @foreach ($secondaryDependency['model']::all() as $connectedEntityEntry)
            @php
                $colSize = isset($secondaryDependency['number_columns'])
                    ? intval(12/$secondaryDependency['number_columns'])
                    : '4';
            @endphp
            <div class="col-sm-{{ $colSize }}">
                <div class="form-check">
                    <input type="checkbox"
                        class='form-check-input secondary_list'
                        data-id="{{ $connectedEntityEntry->id }}"
                        @foreach ($secondaryDependency as $attribute => $value)
                            @if (is_string($attribute) && $attribute != 'value')
                                @if ($attribute=='name')
                                    {{ $attribute }}="{{ $value }}_show[]"
                                @else
                                    {{ $attribute }}="{{ $value }}"
                                @endif
                            @endif
                        @endforeach
                        value="{{ $connectedEntityEntry->id }}"
                        @if (
                            !empty($secondaryDependencyValue)
                            && (
                                in_array($connectedEntityEntry->id, $secondaryDependencyValue)
                                || isset($secondaryIds[$connectedEntityEntry->id])
                            )
                        )
                            checked="checked"
                            @if (isset( $secondaryIds[$connectedEntityEntry->id]))
                                disabled=disabled
                            @endif
                        @endif
                    >
                    <label class="form-check-label">
                        {{ $connectedEntityEntry->{$secondaryDependency['attribute']} }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <br>
        <div class="form-text">{!! $field['hint'] !!}</div>
    @endif

  </div>

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields))

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
    {{-- include checklist_dependency js--}}
    <script>
        onDocumentReady((event) => {
            
            $('.checklist_dependency').each(function(index, item) {
                const uniqueName = $(this).data('entity');
                const dependencyJson = window[uniqueName];
                
                const thisField = $(this);
                thisField.find('.primary_list').change(function() {
                    
                    const currentId = $(this).data('id');
                    if ($(this).is(':checked')) {
                        
                        // Add hidden field with this value
                        const nameInput = thisField.find('.hidden_fields_primary').data('name');
                        const inputToAdd = $('<input type="hidden" class="primary_hidden" name="' + nameInput + '[]" value="' + currentId + '">');
                        
                        thisField.find('.hidden_fields_primary').append(inputToAdd);
                        
                        $.each(dependencyJson[currentId], function(key, value) {
                            // Check and disable secondaries checkbox
                            thisField.find('input.secondary_list[value="' + value + '"]').prop("checked", true);
                            thisField.find('input.secondary_list[value="' + value + '"]').prop("disabled", true);
                            
                            // Remove hidden fields with secondary dependency if was set
                            const hidden = thisField.find('input.secondary_hidden[value="' + value + '"]');
                            if (hidden) {
                                hidden.remove();
                            }
                        });
                        
                    } else {
                        // Remove hidden field with this value
                        thisField.find('input.primary_hidden[value="' + currentId + '"]').remove();
                        
                        // Uncheck and active secondary checkboxes if are not in other selected primary.
                        const secondary = dependencyJson[currentId];
                        
                        const selected = [];
                        thisField.find('input.primary_hidden').each(function (index, input) {
                            selected.push($(this).val());
                        });
                        
                        $.each(secondary, function(index, secondaryItem) {
                            let ok = 1;
                            $.each(selected, function(index2, selectedItem) {
                                if (dependencyJson[selectedItem].indexOf(secondaryItem) !== -1) {
                                    ok = 0;
                                }
                            });
                            
                            if (ok) {
                                thisField.find('input.secondary_list[value="' + secondaryItem + '"]').prop('checked', false);
                                thisField.find('input.secondary_list[value="' + secondaryItem + '"]').prop('disabled', false);
                            }
                        });
                        
                    }
                });
                
                thisField.find('.secondary_list').click(function() {
                    const currentId = $(this).data('id');
                    if ($(this).is(':checked')) {
                        // Add hidden field with this value
                        const nameInput = thisField.find('.hidden_fields_secondary').data('name');
                        const inputToAdd = $('<input type="hidden" class="secondary_hidden" name="' + nameInput + '[]" value="' + currentId + '">');
                        
                        thisField.find('.hidden_fields_secondary').append(inputToAdd);
                    } else {
                        // Remove hidden field with this value
                        thisField.find('input.secondary_hidden[value="' + currentId + '"]').remove();
                    }
                });
            });
            
        });
    </script>
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
