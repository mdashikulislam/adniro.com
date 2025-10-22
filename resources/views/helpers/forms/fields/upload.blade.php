{{-- text input --}}
@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	use Illuminate\Support\Facades\Storage;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'upload';
	$type = 'file';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$attributes ??= [];
	
	$diskName ??= StorageDisk::getDiskName();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$fileUrl = rescue(fn () => Storage::disk($diskName)->url($value));
	
	$hiddenClass = !empty($value) ? 'hidden' : '';
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', $hiddenClass);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			{{-- Show the file name and a "Clear" button on EDIT form. --}}
			@if (!empty($value))
				<div class="well well-sm">
					<a target="_blank" href="{{ $fileUrl }}">{{ $value }}</a>
					<a id="{{ $name }}-file-clear-button" href="#" class="btn btn-secondary btn-xs float-end" title="Clear file">
						<i class="fa-solid fa-xmark"></i>
					</a>
					<div class="clearfix"></div>
				</div>
			@endif
			
			{{-- Show the file picker on CREATE form. --}}
			<input
					type="file"
					id="{{ $name }}-file-input"
					name="{{ $name }}"
					value="{{ $value }}"
					@include('helpers.forms.attributes.field')
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
	{{-- no scripts --}}
	<script>
		var inputSelector = '#{{ $name }}-file-input';
		var fileClearBtn = '#{{ $name }}-file-clear-button';
		
		$(fileClearBtn).click(function (e) {
			e.preventDefault();
			
			$(this).parent().addClass('hidden');
			
			const input = $(inputSelector);
			input.removeClass('hidden');
			input.attr('value', '').replaceWith(input.clone(true));
			
			/* Add a hidden input with the same name, so that the setXAttribute method is triggered */
			$('<input type="hidden" name="{{ $name }}" value="">').insertAfter(inputSelector);
		});
		
		$(inputSelector).change(function () {
			console.log($(this).val());
			/* Remove the hidden input, so that the setXAttribute method is no longer triggered */
			$(this).next('input[type=hidden]').remove();
		});
	</script>
@endpush
