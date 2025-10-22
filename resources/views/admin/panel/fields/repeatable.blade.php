{{-- repeatable --}}
{{-- Repeater: https://github.com/DubFriend/jquery.repeater --}}
{{-- Sortable: https://github.com/SortableJS/Sortable --}}
{{-- SweetAlert2: https://sweetalert2.github.io/ --}}
@php
	$field ??= [];
	
	$label = $field['label'] ?? '';
	$name = $field['name'];
	$id = $field['id'] ?? null;
	$hint = $field['hint'] ?? null;
	$isRequired = $field['required'] ?? false;
	$wrapper = $field['wrapper'] ?? [];
	
	$id = getFieldIdentifier(id: $id, name: $name, prefix: 'repeater');
	
	$subfields = $field['subfields'] ?? $field['fields'] ?? [];
	$subfields = collect($subfields)
		->filter(function ($item) {
			$supportedSubfieldType = [
				'checkbox'          => 'checkbox',
				'date'              => 'date',
				'color'             => 'color',
				'datetime'          => 'datetimeLocal', // datetime-local
				'month'             => 'month',
				'number'            => 'number',
				'search'            => 'search',
				'tel'               => 'tel',
				'time'              => 'time',
				'week'              => 'week',
				'button'            => 'button',
				'text'              => 'text',
				'url'               => 'url',
				'email'             => 'email',
				'password'          => 'password',
				'range'             => 'range',
				'textarea'          => 'textarea',
				'select'            => 'select',
				'select_from_array' => 'select',
				'select_multiple'   => 'select[multiple]',
				'radio'             => 'radio',
				'upload'            => 'file',
				'upload_multiple'   => 'file[multiple]',
				'hidden'            => 'hidden',
			];
			$type = $item['type'] ?? '';
			
			return in_array($type, array_keys($supportedSubfieldType));
		})->map(function ($item) {
			$class = $item['wrapper']['class'] ?? '';
			$item['wrapper']['class'] =  "{$class} subfield";
			
			return $item;
		})->toArray();
	
	$defaultValues = $field['default_values'] ?? [];
	$defaultValues = collect($defaultValues)
		->mapWithKeys(function ($item, $key) {
			$type = $item['type'] ?? '';
			// $suffix = '-input';
			$suffix = '';
			$key = !str_ends_with($key, $suffix) ? "{$key}{$suffix}" : $key;
			
			return [$key => $item];
		})->toArray();
	// dump($defaultValues);
	$defaultValues = json_encode($defaultValues);
	
	$newItemLabel = $field['new_item_label'] ?? trans('admin.add');
	$initRows = (int)($field['init_rows'] ?? 1);
	$minRows = (int)($field['min_rows'] ?? 1);
	$maxRows = (int)($field['max_rows'] ?? 2);
	
	$initRows = ($initRows < 0) ? 0 : $initRows;
	$requiredRows = ($initRows < 1) ? 1 : $initRows;
	$minRows = ($minRows < 0) ? 0 : $minRows;
	$maxRows = ($maxRows < 2) ? 2 : $maxRows;
	/*
	 * Allow Reordering
	 * false; hide up&down arrows next to each row (no reordering)
	 * true; show up&down arrows next to each row
	 * 'order'; show arrows AND add a hidden subfield with that name (value gets updated when rows move)
	 * ['name' => 'order', 'type' => 'number', 'attributes' => ['data-reorder-input' => true]]; show arrows AND add a visible number subfield
	 */
	$reorder = $field['reorder'] ?? '';
	$dragLabel = $field['drag_label'] ?? trans('admin.drag');
	$deleteLabel = $field['delete_label'] ?? trans('admin.delete');
	
	$dotSepName = arrayFieldToDotNotation($name);
	
	$value = $field['value'] ?? $field['default'] ?? null;
	$value = old($dotSepName, $value);
	
	$existingData = json_encode($value);
@endphp
@if (!empty($subfields))
	<div @include('admin.panel.inc.field_wrapper_attributes')>
		<label class="form-label fw-bolder">
			{!! $label !!}
			@if ($isRequired)
				<span class="text-danger">*</span>
			@endif
		</label>
		
		{{-- HINT --}}
		@if (!empty($hint))
			<div class="form-text mb-2">{!! $hint !!}</div>
		@endif
		<div class="repeater" id="{{ $id }}">
			<div data-repeater-list="{{ $name }}">
				@for ($i = 1; $i <= $requiredRows; $i++)
					<div class="card mb-2 bg-light-subtle" data-repeater-item>
						<div class="card-body py-2">
							<div class="row mb-2">
								@foreach($subfields as $subfield)
									@include('admin.panel.fields.' . $subfield['type'], ['field' => $subfield])
								@endforeach
								<div class="col-md-12 d-flex justify-content-between">
									@if ($reorder)
										<span class="drag-handle">☰ {!! $dragLabel !!}</span>
									@endif
									<button type="button" data-repeater-delete class="btn btn-danger btn-xs">
										<i class="bi bi-x-lg"></i> {{ $deleteLabel }}
									</button>
								</div>
							</div>
						</div>
					</div>
				@endfor
			</div>
			<button type="button" data-repeater-create class="btn btn-secondary btn-xs">
				<i class="bi bi-plus-lg"></i> {{ $newItemLabel }}
			</button>
		</div>
	</div>
@endif

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}

@if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields))
	{{-- FIELD CSS - will be loaded in the after_styles section --}}
	@push('crud_fields_styles')
	@endpush
	
	{{-- FIELD JS - will be loaded in the after_scripts section --}}
	@push('crud_fields_scripts')
		<script src="{{ asset('assets/plugins/sortablejs/1.15.6/Sortable.min.js') }}"></script>
		<script src="{{ asset('assets/plugins/jquery.repeater/1.2.2/jquery.repeater.js') }}"></script>
	@endpush

@endif

@push('crud_fields_scripts')
	<script>
		onDocumentReady((event) => {
			const MIN_ITEMS = {{ $minRows }};
			const MAX_ITEMS = {{ $maxRows }};
			
			const id = '{{ $id }}';
			const createBtnElement = $(`#${id} [data-repeater-create]`);
			
			const initEmpty = {{ ($initRows > 0) ? 'false' : 'true' }};
			const defaultValues = {!! $defaultValues !!};
			const reorder = {{ $reorder ? 'true' : 'false' }};
			const existingData = {!! $existingData !!};
			
			const options = {
				initEmpty: initEmpty,
				defaultValues: defaultValues,
				show: function () {
					$(this).slideDown();
					
					const allElements = $(`#${id}.repeater [data-repeater-item]`);
					
					{{-- For Maximum Repetition --}}
					{{-- Hide "Add" button if max reached --}}
					if (allElements.length >= MAX_ITEMS) {
						createBtnElement.hide();
					}
				},
				hide: async function (deleteElement) {
					const allElements = $(`#${id}.repeater [data-repeater-item]`);
					
					{{-- For Minimum Repetition (more than 1) --}}
					if (allElements.length <= MIN_ITEMS) {
						alert('You must have at least ' + MIN_ITEMS + ' items');
						return; /* Don't delete */
					}
					
					const confirmationMessage = 'Are you sure you want to delete this element?';
					const confirmedAction = await swalConfirm(confirmationMessage);
					if (confirmedAction) {
						$(this).slideUp(deleteElement);
						
						{{-- For Maximum Repetition --}}
						{{-- Show "Add" button when item is deleted --}}
						createBtnElement.show();
					}
				},
				ready: function (setIndexes) {
					if (!reorder) {
						return;
					}
					
					// Create the drag and drop functionality
					const dragAndDropElement = document.querySelector(`#${id} [data-repeater-list]`);
					if (!dragAndDropElement) {
						return;
					}
					
					const sortableInstance = new Sortable(dragAndDropElement, {
						handle: '.drag-handle',
						animation: 150,
						ghostClass: 'sortable-ghost',
						onEnd: function (evt) {
							setIndexes();
						}
					});
				},
				isFirstItemUndeletable: true
			};
			
			const $repeater = $(`#${id}.repeater`).repeater(options);
			
			{{-- Set the existing data from database --}}
			@if (!empty($value))
				$repeater.setList(existingData);
			@endif
		});
	</script>
@endpush

{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
