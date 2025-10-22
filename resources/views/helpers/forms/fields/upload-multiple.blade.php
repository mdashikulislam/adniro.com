{{-- upload multiple input --}}
@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	use Illuminate\Support\Facades\Storage;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'upload-multiple';
	$type = 'file';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= [];
	$default ??= [];
	$required ??= false;
	$attributes ??= [];
	
	$attributes = array_merge($attributes, ['multiple' => true]);
	$diskName ??= StorageDisk::getDiskName();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? []);
	// $value = old($dotSepName, $value);
	
	if (!empty($value) && is_array($value)) {
		$value = collect($value)
			->map(function ($path) use ($diskName) {
				$url = rescue(fn () => Storage::disk($diskName)->url($path));
				return [
					'path' => $path,
					'url'  => $url,
				];
			})->toArray();
	}
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			{{-- Show the file name and a "Clear" button on EDIT form --}}
			@if (!empty($value))
				<div class="well well-sm file-preview-container">
					@foreach($value as $key => $file)
						@php
							$url = $file['url'] ?? null;
							$path = $file['path'] ?? null;
						@endphp
						<div class="file-preview">
							<a target="_blank" href="{{ $url }}">{{ $path }}</a>
							<a id="{{ $name }}_{{ $key }}_clear_button"
								href="#"
								class="btn btn-secondary btn-xs float-end file-clear-button"
								title="Clear file"
								data-filename="{{ $path }}"
							><i class="fa-solid fa-xmark"></i></a>
							<div class="clearfix"></div>
						</div>
					@endforeach
				</div>
			@endif
			
			{{-- Show the file picker on CREATE form. --}}
			<input name="{{ $name }}[]" type="hidden" value="">
			<input
					type="file"
					id="{{ $name }}-file-input"
					name="{{ $name }}[]"
					value=""
					@include('helpers.forms.attributes.field', ['attributes' => $attributes])
			>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- FIELD EXTRA JS --}}
{{-- push things in the after_scripts section --}}
@push("{$viewName}_helper_scripts")
	<script>
		var fieldName = '{{ $name }}';
		var fileInputSelector = `#${fieldName}-file-input`;
		
		$(".file-clear-button").click(function (e) {
			e.preventDefault();
			
			const container = $(this).parent().parent();
			const parent = $(this).parent();
			
			/* Remove the filename and button */
			parent.remove();
			
			/* If the file container is empty, remove it */
			if ($.trim(container.html()) === '') {
				container.remove();
			}
			
			const filename = $(this).data('filename');
			const clearFieldEl = $(`<input type="hidden" name="clear_${fieldName}[]" value="${filename}">`);
			clearFieldEl.insertAfter(fileInputSelector);
		});
		
		$(fileInputSelector).change(function () {
			console.log($(this).val());
			/* Remove the hidden input, so that the setXAttribute method is no longer triggered */
			$(this).next('input[type=hidden]').remove();
		});
	</script>
@endpush
