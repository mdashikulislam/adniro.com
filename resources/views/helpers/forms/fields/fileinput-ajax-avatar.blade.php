{{-- fileinput-ajax-avatar --}}
{{-- https://github.com/kartik-v/bootstrap-fileinput --}}
{{-- https://plugins.krajee.com/file-avatar-upload-demo --}}
{{-- https://plugins.krajee.com/file-input#ajax-submission --}}
{{-- https://plugins.krajee.com/file-input-ajax-demo/1 --}}
{{-- SweetAlert2: https://sweetalert2.github.io/ --}}
@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	use Illuminate\Support\Facades\Storage;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= [];
	$viewName = 'fileinput-ajax-avatar';
	$type = 'file';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null; // e.g. [key => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext']
	$default ??= null; // e.g. [key => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext']
	$placeholder ??= null;
	$required ??= false;
	$hint ??= t('Click to select');
	$attributes ??= [];
	
	$diskName ??= StorageDisk::getDiskName();
	$fileLoadingMessage ??= t('loading_wd');
	$_method ??= 'PUT';
	$deleteUrlPattern ??= '/';
	$previewFrameWidth ??= null;
	$previewFrameHeight ??= null;
	$elTargetContainer ??= '#userImg';
	$pluginOptions ??= [];
	
	$theme = $pluginOptions['theme'] ?? config('larapen.core.fileinput.theme', 'bs5');
	$language = $pluginOptions['language'] ?? app()->getLocale();
	$rtl = $pluginOptions['rtl'] ?? ((config('lang.direction') == 'rtl') ? 'true' : 'false');
	
	$defaultAllowedFileFormats = getServerAllowedImageFormats();
	$allowedFileExtensions = $pluginOptions['allowedFileExtensions'] ?? $defaultAllowedFileFormats;
	$defaultMinFileSize = config('settings.upload.min_image_size', 0);
	$defaultMaxFileSize = config('settings.upload.max_image_size', 1000);
	$minFileSize = $pluginOptions['minFileSize'] ?? $defaultMinFileSize;
	$maxFileSize = $pluginOptions['maxFileSize'] ?? $defaultMaxFileSize;
	$showPreview = $pluginOptions['showPreview'] ?? 'false';
	
	$browseClass = $pluginOptions['browseClass'] ?? 'btn btn-primary';
	$showRemove = $pluginOptions['fileActionSettings']['showRemove'] ?? 'true';
	$showZoom = $pluginOptions['fileActionSettings']['showZoom'] ?? 'true';
	$removeClass = $pluginOptions['fileActionSettings']['removeClass'] ?? 'btn btn-outline-danger btn-sm';
	$zoomClass = $pluginOptions['fileActionSettings']['zoomClass'] ?? 'btn btn-outline-secondary btn-sm';
	
	$uploadUrl = $pluginOptions['uploadUrl'] ?? url('/');
	$_method = (!empty($_method) && in_array($_method, ['POST', 'PUT'])) ? $_method : 'POST';
	$uploadExtraData = $pluginOptions['uploadExtraData'] ?? ['_token' => csrf_token(), '_method' => $_method];
	$elSuccessContainer = $pluginOptions['elSuccessContainer'] ?? '#avatarUploadSuccess';
	$elErrorContainer = $pluginOptions['elErrorContainer'] ?? '#avatarUploadError';
	$msgErrorClass = $pluginOptions['msgErrorClass'] ?? 'alert alert-block alert-danger';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? []);
	// $value = old($dotSepName, $value);
	
	$defaultKey = generateRandomString(type: 'numeric');
	$defaultFilePath = config('larapen.media.avatar');
	$defaultFileUrl = thumbParam($defaultFilePath)->url();
	
	$key = castToString($value['key'] ?? $defaultKey, $defaultKey);
	$filePath = castToStringOrNull($value['path'] ?? null);
	$fileUrl = castToStringOrNull($value['url'] ?? null);
	if (!empty($filePath) && Storage::disk($diskName)->exists($filePath)) {
		if (empty($fileUrl)) {
			// $fileUrl = thumbService($filePath)->resize('avatar')->url();
			$fileUrl = rescue(fn () => Storage::disk($diskName)->url($filePath));
		}
		$fileSize = rescue(fn () => Storage::disk($diskName)->size($filePath), 0);
	}
	$fileUrl = $fileUrl ?? $defaultFileUrl;
	$deleteUrl = str_replace(['{index}', '{id}', '{key}'], $key, $deleteUrlPattern);
	$avatarAlt = t('Your Photo or Avatar');
	
	// file-preview-frame
	$defaultPreviewFrameWidth = 180; // 150;
	$defaultPreviewFrameHeight = 192; // 160;
	$previewFrameWidth = (!empty($previewFrameWidth) && is_integer($previewFrameWidth)) ? $previewFrameWidth : $defaultPreviewFrameWidth;
	$previewFrameHeight = (!empty($previewFrameHeight) && is_integer($previewFrameHeight)) ? $previewFrameHeight : $defaultPreviewFrameHeight;
	
	$fiAvatarClass = $isHorizontal ? 'mb-3 row' : 'mb-3 col-md-12';
	$wrapper = \App\Helpers\Common\Html\HtmlAttr::append($wrapper, 'class', $fiAvatarClass);
	if ($rtl == 'true') {
		$wrapper['dir'] = 'rtl';
	}
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'file');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	<div class="card">
		@if (!empty($label))
			<div class="card-header">
				<h5 class="card-title mb-0">{!! $label !!}</h5>
			</div>
		@endif
		<div class="card-body">
			<div class="row">
				<div class="col-md-12 text-center">
					
					<div class="avatar-field">
						<div class="file-loading">
							<input
									type="file"
									id="avatar_{{ $id }}"
									name="{{ $name }}"
									@include('helpers.forms.attributes.field')
							>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</div>
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

@pushonce("{$viewName}_assets_styles")
	<style>
		/* Avatar Upload */
		.avatar-field {
			display: inline-block;
			vertical-align: middle;
		}
		
		.avatar-field .krajee-default.file-preview-frame,
		.avatar-field .krajee-default.file-preview-frame:hover {
			margin: 0;
			padding: 0;
			border: none;
			box-shadow: none;
			text-align: center;
		}
		
		.avatar-field .file-input {
			display: table-cell;
			width: {{ $previewFrameWidth }}px;
		}
		
		.avatar-field .krajee-default.file-preview-frame .kv-file-content,
		.avatar-field .krajee-default.file-preview-frame .kv-file-content img.file-preview-image,
		.avatar-field .file-preview-thumbnails .file-default-preview img {
			width: {{ $previewFrameWidth }}px !important;
			height: auto;
			max-height: {{ $previewFrameHeight }}px !important;
		}
		
		.kv-reqd {
			color: red;
			font-family: monospace;
			font-weight: normal;
		}
		
		.file-preview {
			padding: 2px;
		}
		
		.file-drop-zone {
			margin: 2px;
			min-height: {{ ($previewFrameHeight * 100)/$defaultPreviewFrameHeight }}px;
		}
		
		.file-drop-zone .file-preview-thumbnails {
			cursor: pointer;
		}
		
		.krajee-default.file-preview-frame .file-thumbnail-footer {
			height: 30px;
		}
		
		/* Allow clickable uploaded photos (Not possible) */
		.file-drop-zone {
			padding: 5px;
		}
		
		.file-drop-zone .kv-file-content {
			padding: 0;
		}
		
		.avatar-field .kv-file-content img.file-preview-image {
			border-radius: 4px;
		}
	</style>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		var defaultFileUrl = '{{ $defaultFileUrl }}';
		var defaultAvatarAlt = '{{ $avatarAlt }}';
		var uploadHint = '<h6 class="text-muted pb-0">{{ $hint }}</h6>';
		var elSuccessContainer = '{{ $elSuccessContainer }}';
		var elErrorContainer = '{{ $elErrorContainer }}';
		var elTargetContainer = '{{ $elTargetContainer }}';
		var deleteLabel = '{{ t('Delete') }}';
		
		{{-- fileinput Options --}}
		var fiOptions = {};
		fiOptions.theme = '{{ $theme }}';
		fiOptions.language = '{{ $language }}';
		fiOptions.rtl = {{ $rtl }};
		fiOptions.showClose = false;
		fiOptions.showCaption = false; {{-- input field --}}
		fiOptions.showRemove = true;   {{-- only clears preview - option removed from layoutTemplates.main2 --}}
		fiOptions.removeLabel = deleteLabel;
		fiOptions.removeClass = 'btn btn-danger';
		fiOptions.showBrowse = true;
		fiOptions.browseClass = '{{ $browseClass }}';
		fiOptions.browseOnZoneClick = true;
		
		fiOptions.uploadUrl = '{{ $uploadUrl }}';
		fiOptions.uploadAsync = false;
		fiOptions.uploadExtraData = {!! collect($uploadExtraData)->toJson() !!};
		fiOptions.elErrorContainer = elErrorContainer;
		fiOptions.msgErrorClass = '{{ $msgErrorClass }}';
		
		fiOptions.showPreview = true;
		fiOptions.overwriteInitial = true;
		fiOptions.defaultPreviewContent = '<img src="' + defaultFileUrl + '" alt="' + defaultAvatarAlt + '">' + uploadHint;
		fiOptions.initialPreviewAsData = true;
		fiOptions.initialPreviewFileType = 'image';
		fiOptions.allowedFileExtensions = {!! collect($allowedFileExtensions)->toJson() !!};
		fiOptions.minFileSize = {{ (int)$minFileSize }};
		fiOptions.maxFileSize = {{ (int)$maxFileSize }};
		fiOptions.minFileCount = 0;
		fiOptions.maxFileCount = 1;
		fiOptions.validateInitialCount = true;
		
		fiOptions.fileActionSettings = {
			showDrag: false,
			showRemove: {{ $showRemove }},
			showZoom: {{ $showZoom }},
			removeClass: '{{ $removeClass }}',
			zoomClass: '{{ $zoomClass }}'
		};
		
		{{-- Variables for layoutTemplates --}}
		let actions, footer, main2;
		actions = '<div class="file-actions">\n'
			+ '<div class="file-footer-buttons">\n{delete} {zoom}</div>\n'
			+ '<div class="clearfix"></div>\n'
			+ '</div>';
		footer = '<div class="file-thumbnail-footer d-flex justify-content-center mt-2">\n{actions}\n</div>';
		main2 = '{preview}\n<div class="kv-upload-progress hide"></div>\n{browse}';
		
		{{-- layoutTemplates --}}
		fiOptions.layoutTemplates = {
			main2: main2,
			actions: actions,
			footer: footer
		};
		
		fiOptions.initialPreview = [];
		fiOptions.initialPreviewConfig = [];
		@if (!empty($filePath) && Storage::disk($diskName)->exists($filePath))
			fiOptions.initialPreview[0] = '{{ $fileUrl }}';
			fiOptions.initialPreviewConfig[0] = {};
			fiOptions.initialPreviewConfig[0].key = {{ (int)$key }};
			fiOptions.initialPreviewConfig[0].caption = '{{ basename($filePath) }}';
			fiOptions.initialPreviewConfig[0].size = {{ $fileSize }};
			fiOptions.initialPreviewConfig[0].url = '{{ $deleteUrl }}';
			fiOptions.initialPreviewConfig[0].extra = fiOptions.uploadExtraData;
		@endif
		
		onDocumentReady((event) => {
			{{-- fileinput --}}
			const avatarFieldEl = $('#avatar_{{ $id }}');
			avatarFieldEl.fileinput(fiOptions);
			
			{{-- Before upload hook --}}
			avatarFieldEl.on('filebatchpreupload', (event, data) => {
				{{-- Empty & hide the success element container --}}
				const uploadSuccessEl = $(elSuccessContainer);
				if (uploadSuccessEl.length) {
					uploadSuccessEl.html('<ul class="mb-0 list-unstyled"></ul>').hide();
				}
			});
			
			{{-- File selected hook (from "Browse" button or drag-n-drop) --}}
			avatarFieldEl.on('filebatchselected', (event, files) => {
				{{-- Auto upload the selected file --}}
				$(event.target).fileinput('upload');
			});
			
			{{-- After successful upload hook --}}
			avatarFieldEl.on('filebatchuploadsuccess', (event, data) => {
				if (!data.files || data.files.length <= 0) {
					return;
				}
				
				{{-- Get the success file uploaded message --}}
				const successfulMessage = '{!! escapeStringForJs(t('fileinput_file_uploaded_successfully')) !!}';
				
				{{-- Customized the success message for each uploded file --}}
				let message = '';
				$.each(data.files, (key, file) => {
					if (typeof file !== 'undefined') {
						let fileName = file.name;
						fileName = `<strong>${fileName}</strong>`;
						
						let fileMessage = `<li class="lh-lg"><i class="bi bi-check-lg me-1"></i>${successfulMessage}</li>`;
						fileMessage = fileMessage.replace('{fileName}', fileName);
						
						message = message + fileMessage;
					}
				});
				
				{{-- Show uploads success messages --}}
				const uploadSuccessEl = $(elSuccessContainer);
				if (uploadSuccessEl.length) {
					uploadSuccessEl.find('ul').append(message);
					uploadSuccessEl.fadeIn('slow');
				}
				
				{{-- Hide the progress bar after delay (in milliseconds) --}}
				const delay = 1000;
				setTimeout(() => {
					const progressBarEl = $('.kv-upload-progress');
					if (progressBarEl.length) {
						progressBarEl.hide();
					}
				}, delay);
				
				{{-- Update the avatar img tag src --}}
				const targetEl = $(elTargetContainer);
				const fileInputAvatarEl = $('.avatar-field .kv-file-content .file-preview-image');
				if (targetEl.length && fileInputAvatarEl.length) {
					targetEl.attr({'src': fileInputAvatarEl.attr('src')});
				}
			});
			
			{{-- Before deletion hook --}}
			{{--
			- filebeforedelete: This event is triggered on click of the delete button of each initial preview
			thumbnail file in the initialPreview content set. Even fired before the ajax request is initiated
			and can help one to validate if the ajax request needs to be initiated.
			
			- filepredelete: This event is triggered before ajax request for deletion is sent to the server
			of each initial preview thumbnail file in the initialPreview content set.
			Event fired after the ajax request is initiated and can help one to abort the ajax request if desired based on validation.
			--}}
			avatarFieldEl.on('filebeforedelete', async (event, key, data) => {
				const deleteFileConfirmQuestion = "{{ t('confirm_picture_deletion') }}";
				const confirmedAction = await swalConfirm(deleteFileConfirmQuestion);
				return !confirmedAction;
			});
			
			{{-- After deletion hook --}}
			avatarFieldEl.on('filedeleted', (event, key, jqXHR, data) => {
				{{-- Update the avatar img tag src with the default avatar URL --}}
				const targetEl = $(elTargetContainer);
				if (targetEl.length) {
					targetEl.attr({'src': defaultFileUrl});
				}
				
				{{-- Show the deletion success message --}}
				const message = "{{ t('avatar_has_been_deleted') }}";
				const uploadSuccessEl = $(elSuccessContainer);
				if (uploadSuccessEl.length) {
					uploadSuccessEl.html('<ul class="mb-0 list-unstyled"><li class="lh-lg"></li></ul>').hide();
					uploadSuccessEl.find('ul li').append(`<i class="bi bi-check-lg me-1"></i>${message}`);
					uploadSuccessEl.fadeIn('slow');
				}
			});
			
			{{-- Triggered when preview image is clicked --}}
			{{-- Zoom clicked previewed image --}}
			{{-- Preview Selector: .avatar-field .file-preview-thumbnails .file-preview-frame --}}
			const thumbnailsImgSelector = '.avatar-field .kv-file-content img.file-preview-image';
			$(document).on('click', thumbnailsImgSelector, function () {
				const thumbnailEl = this.closest('.file-preview-frame');
				if (thumbnailEl) {
					avatarFieldEl.fileinput('zoom', $(thumbnailEl).attr('id'));
				}
			});
		});
	</script>
@endpush
