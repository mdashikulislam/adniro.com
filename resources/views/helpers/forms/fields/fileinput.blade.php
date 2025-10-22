{{-- fileinput --}}
{{-- https://github.com/kartik-v/bootstrap-fileinput --}}
{{-- https://plugins.krajee.com/file-input --}}
{{-- https://plugins.krajee.com/file-input/demo --}}
@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	use Illuminate\Support\Facades\Storage;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= [];
	$viewName = 'fileinput';
	$type = 'file';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null; // e.g. ['key' => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext'] (or array of that)
	$default ??= null; // e.g. ['key' => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext'] (or array of that)
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$diskName ??= StorageDisk::getDiskName();
	$disk = Storage::disk($diskName);
	$fileLoadingMessage ??= t('loading_wd');
	$allowsMultiple ??= false;
	$limit ??= 5;
	$limit = $allowsMultiple ? $limit : 1;
	$attributes = $allowsMultiple ? array_merge($attributes, ['multiple' => true]) : $attributes;
	$deleteUrlPattern ??= '/';
	$downloadable ??= false;
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
	$dropZoneTitle = (!$allowsMultiple || $limit <= 1) ? $dropZoneTitle : null;
	
	$showCaption = $pluginOptions['showCaption'] ?? 'true'; // input field
	$showBrowse = $pluginOptions['showBrowse'] ?? 'true'; // input field browse button
	$browseClass = $pluginOptions['browseClass'] ?? 'btn btn-primary';
	$mainClass = $pluginOptions['mainClass'] ?? null; // 'd-grid'
	$showRemove = $pluginOptions['fileActionSettings']['showRemove'] ?? 'true';
	$showZoom = $pluginOptions['fileActionSettings']['showZoom'] ?? 'true';
	$removeClass = $pluginOptions['fileActionSettings']['removeClass'] ?? 'btn btn-outline-danger btn-sm';
	$zoomClass = $pluginOptions['fileActionSettings']['zoomClass'] ?? 'btn btn-outline-secondary btn-sm';
	
	// Only when multiple upload is allowed
	// {uploadUrl: '...'} is required to allow multiple files selection in many times.
	// Showing the file upload button is cancel with {showUpload: false}
	$uploadUrl = $pluginOptions['uploadUrl'] ?? '/';
	$uploadUrl = $allowsMultiple ? $uploadUrl : null;
	
	$name = $allowsMultiple ? $name . '[]' : $name;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '_', $dotSepName);
	
	$value = $value ?? ($default ?? []);
	// $value = old($name, $value);
	
	$defaultKey = generateRandomString(type: 'numeric');
	$defaultFilePath = config('larapen.media.picture');
	$defaultFileUrl = thumbParam($defaultFilePath)->url();
	
	$wrapper['class'] ??= $isHorizontal ? 'mb-3 row' : 'mb-3 col-md-12';
	if ($rtl == 'true') {
		$wrapper['dir'] = 'rtl';
	}
	
	$attrStr = !empty($placeholder) ? ' data-msg-placeholder="' . $placeholder . '"' : '';
	
	// Preview File Function
	$fnFilePreview = function($idx, $file, $diskName, $disk, $defaultKey, $defaultFileUrl, $deleteUrlPattern) {
		$key = castToString($file['key'] ?? $defaultKey, $defaultKey);
		$filePath = castToStringOrNull($file['path'] ?? null);
		$fileUrl = castToStringOrNull($file['url'] ?? null);
		
		if (empty($fileUrl) && !empty($filePath)) {
			$fileUrl = ($diskName == 'private') ? privateFileUrl($filePath) : fileUrl($filePath);
		}
		
		$out = '';
		
		if (!empty($filePath) && $disk->exists($filePath)) {
			if (empty($fileUrl)) {
				$fileUrl = rescue(fn () => $disk->url($filePath));
			}
			$fileUrl = $fileUrl ?? $defaultFileUrl;
			$fileSize = rescue(fn () => $disk->size($filePath), 0);
			$deleteUrl = str_replace(['{index}', '{id}', '{key}'], $key, $deleteUrlPattern);
			
			$key = (int)$key;
			$fileBasename = basename($filePath);
			$out .= "fiOptions.initialPreview[$idx] = '<img src=\"$fileUrl\" class=\"file-preview-image\">';";
			$out .= "fiOptions.initialPreviewConfig[$idx] = {};";
			$out .= "fiOptions.initialPreviewConfig[$idx].key = $key;";
			$out .= "fiOptions.initialPreviewConfig[$idx].caption = '$fileBasename';";
			$out .= "fiOptions.initialPreviewConfig[$idx].size = $fileSize;";
			$out .= "fiOptions.initialPreviewConfig[$idx].url = '$deleteUrl';";
		}
		
		return $out;
	};
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'file');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<input
					type="file"
					id="{{ 'file_' . $id }}"
					name="{{ $name }}"{!! $attrStr !!}
					@include('helpers.forms.attributes.field')
			>
			<input type="hidden" id="selectedFiles" name="selectedFiles">
			
			@include('helpers.forms.partials.hint')
			
			{{-- Downloadable File --}}
			@if (!$allowsMultiple && $downloadable)
				@php
					$fileToDownloadPath = castToStringOrNull($value['path'] ?? null);
					$fileToDownloadUrl = castToStringOrNull($value['url'] ?? '/');
					
					if (empty($fileToDownloadUrl) && !empty($fileToDownloadPath)) {
						$fileToDownloadUrl = ($diskName == 'private') ? privateFileUrl($fileToDownloadPath) : fileUrl($fileToDownloadPath);
					}
				@endphp
				@if (!empty($fileToDownloadPath) && $disk->exists($fileToDownloadPath))
					<div>
						<a class="btn btn-secondary" href="{{ $fileToDownloadUrl }}" target="_blank">
							<i class="fa-solid fa-paperclip"></i> {{ t('Download') }}
						</a>
					</div>
				@endif
			@endif
			
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
@pushonce("{$viewName}_assets_styles")
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

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ url($pluginBasePath . 'js/plugins/sortable.min.js') }}" type="text/javascript"></script>
	<script src="{{ url($pluginBasePath . 'js/fileinput.min.js') }}" type="text/javascript"></script>
	@if (file_exists($pluginFullPath . 'themes/' . $theme . '/theme.js'))
		<script src="{{ url($pluginBasePath . 'themes/' . $theme . '/theme.js') }}" type="text/javascript"></script>
	@endif
	<script src="{{ url('common/js/fileinput/locales/' . $language . '.js') }}" type="text/javascript"></script>
@endpushonce

@pushonce("fileinput_multiple_selections_assets_scripts")
	<script src="{{ url('assets/js/helpers/extensionMimetype.js') }}" type="text/javascript"></script>
	<script>
		/**
		 * Convert JSON file data array to a FileList object
		 * https://developer.mozilla.org/fr/docs/Web/API/FileList
		 *
		 * @param filesInput
		 * @param unique
		 * @returns {FileList}
		 */
		function fileDataArrayToFileList(filesInput, unique = false) {
			const dataTransfer = new DataTransfer();
			
			/* Handle different input types */
			/* Return empty FileList for null/undefined */
			if (!filesInput) {
				return dataTransfer.files;
			}
			
			/* If it's already a FileList, return it directly */
			if (filesInput instanceof FileList) {
				return filesInput;
			}
			
			/* Convert object to array if needed */
			const array = !Array.isArray(filesInput)
				? Object.values(filesInput) : filesInput;
			
			/* Add each file to the DataTransfer */
			array.forEach(file => {
				let fileObj = null;
				if (file instanceof File) {
					fileObj = file;
				} else if (file.file instanceof Blob) {
					fileObj = fileDataToFileObject(file);
				}
				if (fileObj) {
					/* Check if file already exists */
					const fileExists = unique && Array.from(dataTransfer.files).some(
						f => (f.name === file.name && f.size === file.size)
					);
					if (!fileExists) {
						dataTransfer.items.add(fileObj);
					}
				}
			});
			
			return dataTransfer.files;
		}
		
		/**
		 * Convert JSON file data array to an array of File objects
		 * @param filesInput
		 * @returns {module:buffer.File[]}
		 */
		function fileDataArrayToFileObjectsArray(filesInput) {
			const array = !Array.isArray(filesInput)
				? Object.values(filesInput) : filesInput;
			
			return array.map(file => fileDataToFileObject(file));
		}
		
		/**
		 * Convert a JSON file data object to a File object
		 * @param file
		 * @returns {module:buffer.File}
		 */
		function fileDataToFileObject(file) {
			if (file instanceof File) return file;
			
			return new File([file.file], file.name, {
				type: file.type || getMimeType(file.name),
				lastModified: file.lastModified || Date.now()
			});
		}
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		var allowsMultiple = {{ $allowsMultiple ? 'true' : 'false' }};
		
		{{-- fileinput Options --}}
		var fiOptions = {};
		fiOptions.theme = '{{ $theme }}';
		fiOptions.language = '{{ $language }}';
		fiOptions.rtl = {{ $rtl }};
		fiOptions.showClose = {{ $showClose }};
		fiOptions.showUpload = false;
		fiOptions.showRemove = false;
		fiOptions.showCancel = true;
		fiOptions.showCaption = {{ $showCaption }}; {{-- input field --}}
		fiOptions.showBrowse = {{ $showBrowse }}; {{-- input field browse button --}}
		fiOptions.browseClass = '{{ $browseClass }}';
		@if (!empty($mainClass))
			{{-- requires: showCaption:false, showRemove: false, showUpload: false --}}
			fiOptions.mainClass = '{{ $mainClass }}';
		@endif
		fiOptions.browseOnZoneClick = true;
		
		@if (!empty($uploadUrl))
			fiOptions.uploadUrl = '{{ $uploadUrl }}';
		@endif
		
		fiOptions.showPreview = {{ $showPreview }};
		fiOptions.overwriteInitial = !allowsMultiple;
		fiOptions.dropZoneEnabled = {{ $dropZoneEnabled }};
		fiOptions.browseOnZoneClick = {{ $browseOnZoneClick }};
		fiOptions.previewFileType = 'image';
		fiOptions.allowedFileExtensions = {!! collect($allowedFileExtensions)->toJson() !!};
		fiOptions.minFileSize = {{ (int)$minFileSize }};
		fiOptions.maxFileSize = {{ (int)$maxFileSize }};
		fiOptions.minFileCount = 0;
		fiOptions.maxFileCount = {{ $limit }};
		fiOptions.validateInitialCount = true;
		fiOptions.autoReplace = true;
		
		fiOptions.initialPreview = [];
		fiOptions.initialPreviewConfig = [];
		
		@if ($showPreview == 'true')
			@if (!$allowsMultiple)
				fiOptions.fileActionSettings = {
					showDrag: false,
				};
				fiOptions.layoutTemplates = {
					footer: '<div class="file-thumbnail-footer pt-2">{actions}</div>',
					actionDelete: ''
				};
			@else
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
			
			@if (!empty($value))
				@if ($allowsMultiple)
					@php
						$idx = 0; // Need to be started by 0 to avoid reorder issue
					@endphp
					@foreach($value as $index => $file)
						@if (!empty($file) && is_array($file))
							@php
								$output = $fnFilePreview($idx, $file, $diskName, $disk, $defaultKey, $defaultFileUrl, $deleteUrlPattern);
							@endphp
							@if (!empty($output))
								@php
									echo $output;
									$idx++; // The indexes must follow each other (i.e.: must be consecutive) to avoid eventual issues
								@endphp
							@endif
						@endif
					@endforeach
				@else
					@php
						$output = $fnFilePreview(0, $value, $diskName, $disk, $defaultKey, $defaultFileUrl, $deleteUrlPattern);
						echo $output;
					@endphp
				@endif
			@endif
			
		@endif
		
		onDocumentReady((event) => {
			@if (!empty($dropZoneTitle))
				$.fn.fileinputLocales['{{ $language }}'].dropZoneTitle = '{!! $dropZoneTitle !!}';
			@endif
			
			{{-- fileinput --}}
			const fileInputSelector = 'file_{{ $id }}';
			const fileInputEl = document.getElementById(fileInputSelector);
			
			const $fileInputEl = $(fileInputEl);
			$fileInputEl.fileinput(fiOptions);
			
			/*
			 * Important Notes:
			 * - Browsers don't allow appending to the same <input type="file"> from multiple folder selections. Each new selection replaces the old one.
			 * - To work around this, you must clone the <input type="file"> dynamically after each selection and keep them in the form.
			 *
			 * To allow selecting multiple files from different folders without losing previously selected files using
			 * a standard <input type="file" multiple> (without AJAX) and then process them in PHP, here's a full solution:
			 */
			if (allowsMultiple) {
				/*
				 * Store selected files in a DataTransfer object which can hold File objects
				 * https://developer.mozilla.org/en-US/docs/Web/API/DataTransfer
				 */
				const dataTransfer = new DataTransfer();
				
				if (fiOptions.showPreview && (fiOptions.dropZoneEnabled || fiOptions.browseOnZoneClick)) {
					{{-- File selected hook (from "Browse" button or drag-n-drop) --}}
					$fileInputEl.on('filebatchselected', (event, files) => {
						/* Convert to an array of File objects */
						/* const fileList = fileDataArrayToFileObjectsArray(files); */
						const fileList = fileDataArrayToFileList(files);
						
						for (let i = 0; i < fileList.length; i++) {
							const file = fileList[i];
							
							/* Check if file already exists */
							const fileExists = Array.from(dataTransfer.files).some(
								f => (f.name === file.name && f.size === file.size)
							);
							
							if (!fileExists) {
								dataTransfer.items.add(file);
							}
						}
						
						/* Update the file input with all selected files */
						fileInputEl.files = dataTransfer.files;
					});
				} else {
					/* $fileInputEl change hook (from "Browse" button only) */
					$fileInputEl.on('change', event => {
						/* Add new files to our DataTransfer object */
						for (let i = 0; i < event.target.files.length; i++) {
							const file = event.target.files[i];
							
							/* Check if file already exists */
							const fileExists = Array.from(dataTransfer.files).some(
								f => (f.name === file.name && f.size === file.size)
							);
							
							if (!fileExists) {
								dataTransfer.items.add(file);
							}
						}
						
						/* Update the file input with all selected files */
						fileInputEl.files = dataTransfer.files;
					});
				}
			}
		});
	</script>
@endpush
