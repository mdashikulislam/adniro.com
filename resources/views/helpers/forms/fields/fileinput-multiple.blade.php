{{-- fileinput multiple --}}
{{-- https://github.com/kartik-v/bootstrap-fileinput --}}
{{-- https://plugins.krajee.com/file-input --}}
{{-- https://plugins.krajee.com/file-input/demo --}}
@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= [];
	$viewName = 'fileinput-multiple';
	$type = 'file';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null; // e.g. array of: [key => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext'],
	$default ??= null; // e.g. array of: [key => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext'],
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$diskName ??= StorageDisk::getDiskName();
	$fileLoadingMessage ??= t('loading_wd');
	$limit ??= 5;
	$deleteUrlPattern ??= '/';
	$deleteConfirmQuestion ??= t('confirm_picture_deletion');
	$pluginOptions ??= [];
	
	$theme = $pluginOptions['theme'] ?? config('larapen.core.fileinput.theme', 'bs5');
	$language = $pluginOptions['language'] ?? app()->getLocale();
	$rtl = $pluginOptions['rtl'] ?? ((config('lang.direction') == 'rtl') ? 'true' : 'false');
	
	$previewFileType = $pluginOptions['previewFileType'] ?? null;
	$defaultAllowedFileFormats = ($previewFileType == 'image') ? getServerAllowedImageFormats() : getAllowedFileFormats();
	$allowedFileExtensions = $pluginOptions['allowedFileExtensions'] ?? $defaultAllowedFileFormats;
	$defaultMinFileSize = ($previewFileType == 'image')
		? config('settings.upload.min_image_size', 0)
		: config('settings.upload.min_file_size', 0);
	$defaultMaxFileSize = ($previewFileType == 'image')
		? config('settings.upload.max_image_size', 1000)
		: config('settings.upload.max_file_size', 1000);
	$minFileSize = $pluginOptions['minFileSize'] ?? $defaultMinFileSize;
	$maxFileSize = $pluginOptions['maxFileSize'] ?? $defaultMaxFileSize;
	$fileTypes = ($previewFileType == 'image') ? 'image' : 'file';
	$hint = !empty($hint) ? $hint : t('file_types', ['file_types' => getAllowedFileFormatsHint($fileTypes)]);
	$showPreview = $pluginOptions['showPreview'] ?? 'false';
	
	$showClose = $pluginOptions['showClose'] ?? 'false';
	$dropZoneEnabled = $pluginOptions['dropZoneEnabled'] ?? 'false';
	$browseOnZoneClick = $pluginOptions['browseOnZoneClick'] ?? 'false';
	$dropZoneTitle = $pluginOptions['dropZoneTitle'] ?? null;
	
	$showCaption = $pluginOptions['showCaption'] ?? 'true'; // input field
	$showBrowse = $pluginOptions['showBrowse'] ?? 'true'; // input field browse button
	$browseClass = $pluginOptions['browseClass'] ?? 'btn btn-primary';
	$showRemove = $pluginOptions['fileActionSettings']['showRemove'] ?? 'true';
	$showZoom = $pluginOptions['fileActionSettings']['showZoom'] ?? 'true';
	$removeClass = $pluginOptions['fileActionSettings']['removeClass'] ?? 'btn btn-outline-danger btn-sm';
	$zoomClass = $pluginOptions['fileActionSettings']['zoomClass'] ?? 'btn btn-outline-secondary btn-sm';
	
	$defaultFilePath = config('larapen.media.picture');
	$defaultFileUrl = thumbParam($defaultFilePath)->url();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	// $value = old($dotSepName, $value);
	
	// error
	$errorBag ??= new ViewErrorBag;
	
	$fiMultipleClass = $isHorizontal ? 'mb-3 row' : 'mb-3 col-md-12';
	$wrapper = \App\Helpers\Common\Html\HtmlAttr::append($wrapper, 'class', $fiMultipleClass);
	if ($rtl == 'true') {
		$wrapper['dir'] ??= 'rtl';
	}
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'file fileinput-multiple');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@if (!empty($value))
				@for($i = 1; $i <= $limit; $i++)
					@php
						$fileError = $errorBag->has("$name.$i") ? ' is-invalid' : '';
						$fileId = $value[$i]['id'] ?? $i;
						$placeholderUpdated = str_replace(['{index}', '{id}', '{key}'], $i, $placeholder);
						$phStr = !empty($placeholder) ? ' data-msg-placeholder="' . $placeholderUpdated . '"' : '';
					@endphp
					<div class="mb-2{{ $fileError }}">
						<div class="file-loading">
							<input
									type="file"
									id="file_{{ "{$name}_$i" }}"
									name="{{ $name }}[{{ $fileId }}]"{!! $phStr !!}
									@include('helpers.forms.attributes.field')
							>
						</div>
					</div>
				@endfor
			@else
				@for($i = 1; $i <= $limit; $i++)
					@php
						$fileError = $errorBag->has("$name.$i") ? ' is-invalid' : '';
						$placeholderUpdated = str_replace(['{index}', '{id}', '{key}'], $i, $placeholder);
						$phStr = !empty($placeholder) ? ' data-msg-placeholder="' . $placeholderUpdated . '"' : '';
					@endphp
					<div class="mb-2{{ $fileError }}">
						<div class="file-loading">
							<input
									type="file"
									id="file_{{ "{$name}_$i" }}"
									name="{{ $name }}[]"{!! $phStr !!}
									@include('helpers.forms.attributes.field', ['defaultClass' => 'file fileinput-multiple'])
							>
						</div>
					</div>
				@endfor
			@endif
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
	$pluginBasePath = 'assets/plugins/bootstrap-fileinput/';
	$pluginFullPath = public_path($pluginBasePath);
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("fileinput_assets_styles")
	<link href="{{ url($pluginBasePath . 'css/fileinput.min.css') }}" rel="stylesheet">
	@if ($rtl == 'true')
		<link href="{{ url($pluginBasePath . 'css/fileinput-rtl.min.css') }}" rel="stylesheet">
	@endif
	@if (str_starts_with($theme, 'explorer'))
		<link href="{{ url($pluginBasePath . 'themes/' . $theme . '/theme.min.css') }}" rel="stylesheet">
	@endif
	<style>
		.krajee-default.file-preview-frame:hover:not(.file-preview-error) {
			box-shadow: 0 0 5px 0 #666666;
		}
		.file-loading:before {
			content: " {{ $fileLoadingMessage }}";
		}
	</style>
@endpushonce

@pushonce("fileinput_assets_scripts")
	<script src="{{ url($pluginBasePath . 'js/plugins/sortable.min.js') }}" type="text/javascript"></script>
	<script src="{{ url($pluginBasePath . 'js/fileinput.min.js') }}" type="text/javascript"></script>
	@if (file_exists($pluginFullPath . 'themes/' . $theme . '/theme.js'))
		<script src="{{ url($pluginBasePath . 'themes/' . $theme . '/theme.js') }}" type="text/javascript"></script>
	@endif
	<script src="{{ url('common/js/fileinput/locales/' . $language . '.js') }}" type="text/javascript"></script>
@endpushonce

@pushonce("fileinput_preview_frame_assets_styles")
	<style>
		{{-- Preview Frame Size --}}
		.krajee-default.file-preview-frame .kv-file-content {
			height: auto;
		}
		.krajee-default.file-preview-frame .file-thumbnail-footer {
			height: 30px;
		}
	</style>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		{{-- fileinput Options --}}
		var fiOptions = {};
		fiOptions.theme = '{{ $theme }}';
		fiOptions.language = '{{ $language }}';
		fiOptions.rtl = {{ $rtl }};
		fiOptions.showClose = {{ $showClose }};
		fiOptions.showUpload = false;
		fiOptions.showRemove = false;
		fiOptions.showCaption = {{ $showCaption }}; {{-- input field --}}
		fiOptions.showBrowse = {{ $showBrowse }}; {{-- input field browse button --}}
		fiOptions.browseClass = '{{ $browseClass }}';
		
		fiOptions.showPreview = {{ $showPreview }};
		fiOptions.dropZoneEnabled = {{ $dropZoneEnabled }};
		fiOptions.browseOnZoneClick = {{ $browseOnZoneClick }};
		fiOptions.overwriteInitial = true;
		fiOptions.previewFileType = 'image';
		fiOptions.allowedFileExtensions = {!! collect($allowedFileExtensions)->toJson() !!};
		fiOptions.minFileSize = {{ (int)$minFileSize }};
		fiOptions.maxFileSize = {{ (int)$maxFileSize }};
		fiOptions.minFileCount = 0;
		fiOptions.maxFileCount = 1;
		fiOptions.validateInitialCount = true;
		
		@if ($showPreview == 'true')
			fiOptions.fileActionSettings = {
				showDrag: false,
				showUpload: false,
				showRotate: false,
				showRemove: {{ $showRemove }},
				showZoom: {{ $showZoom }},
				removeClass: '{{ $removeClass }}',
				zoomClass: '{{ $zoomClass }}',
			};
		@endif
		
		onDocumentReady((event) => {
			@if (!empty($dropZoneTitle))
				$.fn.fileinputLocales['{{ $language }}'].dropZoneTitle = '{!! $dropZoneTitle !!}';
			@endif
			
			@if (!empty($value) && $limit > 0)
				@for($i = 1; $i <= $limit; $i++)
					
					fiOptions.initialPreview = [];
					fiOptions.initialPreviewConfig = [];
					
					@php
						$file = $value[$i] ?? null;
					@endphp
					@if (!empty($file) && is_array($file))
						@php
							$key = castToString($file['key'] ?? $i, $i);
							$filePath = castToStringOrNull($file['path'] ?? null);
							$fileUrl = castToStringOrNull($file['url'] ?? null);
						@endphp
						@if (!empty($filePath) && Storage::disk($diskName)->exists($filePath))
							@php
								if (empty($fileUrl)) {
									$fileUrl = rescue(fn () => Storage::disk($diskName)->url($filePath));
								}
								$fileSize = rescue(fn () => Storage::disk($diskName)->size($filePath), 0);
								$fileUrl = $fileUrl ?? $defaultFileUrl;
								$deleteUrl = str_replace(['{index}', '{id}', '{key}'], $key, $deleteUrlPattern);
							@endphp
							fiOptions.initialPreview[0] = '<img src="{{ $fileUrl }}" class="file-preview-image">';
							fiOptions.initialPreviewConfig[0] = {};
							fiOptions.initialPreviewConfig[0].key = {{ (int)$key }};
							fiOptions.initialPreviewConfig[0].caption = '{{ basename($filePath) }}';
							fiOptions.initialPreviewConfig[0].size = {{ $fileSize }};
							fiOptions.initialPreviewConfig[0].url = '{{ $deleteUrl }}';
						@endif
					@endif
					
					var fileinputElSelector = 'input[name="{{ $name }}[{{ $i }}]"]';
					var fileinputEl = $(fileinputElSelector);
					
					if (fileinputEl.length) {
						{{-- fileinput --}}
						fileinputEl.fileinput(fiOptions);
						
						{{-- Before deletion hook --}}
						fileinputEl.on('filepredelete', (event, key, jqXHR, data) => {
							const deleteFileConfirmQuestion = "{{ $deleteConfirmQuestion }}";
							return !confirm(deleteFileConfirmQuestion);
						});
					}
				@endfor
			@else
				{{-- fileinput --}}
				$('input[name^="{{ $name }}["]').fileinput(fiOptions);
			@endif
		});
	</script>
@endpush
