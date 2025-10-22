{{-- fileinput-ajax-multiple (using dropzone) --}}
{{-- https://github.com/kartik-v/bootstrap-fileinput --}}
{{-- https://plugins.krajee.com/file-input#ajax-submission --}}
{{-- https://plugins.krajee.com/file-input-ajax-demo/1 --}}
{{-- SweetAlert2: https://sweetalert2.github.io/ --}}
@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	use Illuminate\Support\Facades\Storage;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = false; // force to false
	
	$viewName = 'fileinput-ajax-multiple';
	$type = 'file';
	$label ??= null;
	$labelClass ??= '';
	$id ??= null;
	$name ??= null;
	$value ??= null; // e.g. array of: [key => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext'],
	$default ??= null; // e.g. array of: [key => 1, 'path' => 'path/to/file.ext', 'url' => 'https://domain/file.ext'],
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$wrapper ??= [];
	$diskName ??= StorageDisk::getDiskName();
	$fileLoadingMessage ??= t('loading_wd');
	$limit = $pluginOptions['limit'] ?? 5;
	$deleteUrlPattern ??= '/';
	$reorderUrl ??= '/';
	$previewFrameWidth ??= null;
	$previewFrameHeight ??= null;
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
	$dropZoneTitle = $pluginOptions['dropZoneTitle'] ?? null;
	
	$browseClass = $pluginOptions['browseClass'] ?? 'btn btn-primary';
	$showRemove = $pluginOptions['fileActionSettings']['showRemove'] ?? 'true';
	$showZoom = $pluginOptions['fileActionSettings']['showZoom'] ?? 'true';
	$removeClass = $pluginOptions['fileActionSettings']['removeClass'] ?? 'btn btn-outline-danger btn-sm';
	$zoomClass = $pluginOptions['fileActionSettings']['zoomClass'] ?? 'btn btn-outline-secondary btn-sm';
	
	$uploadUrl = $pluginOptions['uploadUrl'] ?? '/';
	$_method = (!empty($_method) && in_array($_method, ['POST', 'PUT'])) ? $_method : 'POST';
	$uploadExtraData = $pluginOptions['uploadExtraData'] ?? ['_token' => csrf_token(), '_method' => $_method];
	$elSuccessContainer = $pluginOptions['elSuccessContainer'] ?? '#uploadSuccess';
	$elErrorContainer = $pluginOptions['elErrorContainer'] ?? '#uploadError';
	$msgErrorClass = $pluginOptions['msgErrorClass'] ?? 'alert alert-block alert-danger';
	
	$defaultKey = generateRandomString(type: 'numeric');
	$defaultFilePath = config('larapen.media.picture');
	$defaultFileUrl = thumbParam($defaultFilePath)->url();
	
	// Get steps URLs & labels
	$nextStepUrl ??= '/';
	$nextStepLabel ??= t('submit');
	
	$wrapper['class'] ??= $isHorizontal ? 'mb-3 row' : 'mb-3 col-md-12';
	if ($rtl == 'true') {
		$wrapper['dir'] = 'rtl';
	}
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? []);
	// $value = old($dotSepName, $value);
	
	// file-preview-frame
	$defaultPreviewFrameWidth = 186; // D(228) / 5(186) / 4(241);
	$defaultPreviewFrameHeight = 216; // D(264) / 5(216) / 4(281);
	$previewFrameWidth = (!empty($previewFrameWidth) && is_integer($previewFrameWidth)) ? $previewFrameWidth : $defaultPreviewFrameWidth;
	$previewFrameHeight = (!empty($previewFrameHeight) && is_integer($previewFrameHeight)) ? $previewFrameHeight : $defaultPreviewFrameHeight;
	
	$labelClass = !empty($labelClass) ? " $labelClass" : '';
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'file');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@if (!$isHorizontal)
		<div class="row">
			@endif
			<div class="col-md-12 form-label{{ $labelClass }}">
				{!! $label !!}
				@if ($required)
					<span class="text-danger ms-1">*</span>
				@endif
			</div>
			<div class="col-md-12 text-center pt-2" style="position: relative; float: {!! ($rtl == 'true') ? 'left' : 'right' !!};">
				<div {!! ($rtl == 'true') ? 'dir="rtl"' : '' !!} class="file-loading">
					<input
							type="file"
							id="dz_{{ $id }}"
							name="{{ $name }}[]"
							multiple
							@include('helpers.forms.attributes.field')
					>
				</div>
				
				@include('helpers.forms.partials.hint')
			</div>
			@if (!$isHorizontal)
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

@pushonce("{$viewName}_assets_styles")
	<style>
		.file-drop-zone .krajee-default.file-preview-frame .kv-file-content,
		.file-drop-zone .krajee-default.file-preview-frame .kv-file-content img.file-preview-image {
			width: {{ $previewFrameWidth }}px !important;
			height: auto;
			max-height: {{ $previewFrameHeight }}px !important;
		}
		
		.file-drop-zone .kv-file-content img.file-preview-image {
			cursor: pointer;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script>
		/**
		 * Reorder (Sort) pictures
		 * @param params
		 * @param reorderUrl
		 * @param {string} elSuccessContainer
		 * @param {string} elErrorContainer
		 * @returns {boolean}
		 */
		function reorderPictures(params, reorderUrl, elSuccessContainer, elErrorContainer)
		{
			if (typeof params.stack === 'undefined') {
				return false;
			}
			if (typeof reorderUrl === 'undefined') {
				return false;
			}
			
			/* Unselect any text on the page */
			window.getSelection().removeAllRanges();
			
			/* Un-focus active element */
			if (document.activeElement) {
				document.activeElement.blur();
			}
			
			showWaitingDialog();
			
			let ajax = $.ajax({
				method: 'POST',
				url: reorderUrl,
				data: {
					'params': params,
					'_token': $('input[name=_token]').val()
				}
			});
			ajax.done(function(data) {
				
				hideWaitingDialog();
				
				if (typeof data.status === 'undefined') {
					return false;
				}
				
				const status = parseInt(data.status);
				const message = data.message;
				
				/* Reorder Notification */
				if (status === 1) {
					showSuccessMessage(message, elSuccessContainer, elErrorContainer);
					setTimeout(() => completeWaitingDialog(message), 250);
				} else {
					showErrorMessage(message, elSuccessContainer, elErrorContainer);
				}
				
				return false;
			});
			ajax.fail(function (xhr, textStatus, errorThrown) {
				hideWaitingDialog();
				
				let message = getErrorMessageFromXhr(xhr);
				if (message !== null) {
					showErrorMessage(message, elSuccessContainer, elErrorContainer);
				}
			});
			
			return false;
		}
		
		/**
		 * Reorder (Sort) files
		 * @param params
		 * @param reorderUrl
		 * @param {string} elSuccessContainer
		 * @param {string} elErrorContainer
		 * @returns {Promise<boolean>}
		 */
		async function dzReorderFiles(params, reorderUrl, elSuccessContainer, elErrorContainer) {
			if (typeof params.stack === 'undefined') {
				return false;
			}
			if (typeof reorderUrl === 'undefined') {
				return false;
			}
			
			/* Unselect any text on the page */
			window.getSelection().removeAllRanges();
			
			/* Un-focus active element */
			if (document.activeElement) {
				document.activeElement.blur();
			}
			
			showWaitingDialog();
			
			/* Send the reorder request */
			const _tokenEl = document.querySelector('input[name=_token]');
			let data = {
				'params': params,
				'_token': _tokenEl?.value ?? null
			};
			
			try {
				const json = await httpRequest('POST', reorderUrl, data);
				
				hideWaitingDialog();
				
				if (typeof json.status === 'undefined') {
					return false;
				}
				
				const status = parseInt(json.status);
				const message = json.message;
				
				/* Reorder Notification */
				if (status === 1) {
					showSuccessMessage(message, elSuccessContainer, elErrorContainer);
					setTimeout(() => completeWaitingDialog(message), 250);
				} else {
					showErrorMessage(message, elSuccessContainer, elErrorContainer);
				}
			} catch (error) {
				hideWaitingDialog();
				
				const message = getErrorMessage(error);
				if (message !== null) {
					showErrorMessage(message, elSuccessContainer, elErrorContainer);
				}
			}
			return false;
		}
		
		/**
		 * Show Success Message
		 * @param {string} message
		 * @param {string} elSuccessContainer
		 * @param {string} elErrorContainer
		 */
		function showSuccessMessage(message, elSuccessContainer, elErrorContainer) {
			elSuccessContainer = elSuccessContainer.replace(/^#+/, '');
			elErrorContainer = elErrorContainer.replace(/^#+/, '');
			
			const successEl = document.getElementById(elSuccessContainer);
			const errorEl = document.getElementById(elErrorContainer);
			
			if (errorEl) {
				errorEl.style.display = 'none';
				errorEl.innerHTML = '';
				errorEl.classList.remove('alert', 'alert-block', 'alert-danger');
			}
			
			if (successEl) {
				successEl.innerHTML = '<ul class="mb-0 list-unstyled"></ul>';
				successEl.style.display = 'none';
				successEl.querySelector('ul').innerHTML = message;
				fadeIn(successEl, 'fast'); {{-- 'fast' corresponds to 200ms, 'slow' to 600ms --}}
			}
		}
		
		/**
		 * Show Errors Message
		 * @param {string} message
		 * @param {string} elSuccessContainer
		 * @param {string} elErrorContainer
		 */
		function showErrorMessage(message, elSuccessContainer, elErrorContainer) {
			jsAlert(message, 'error', false);
			
			elSuccessContainer = elSuccessContainer.replace(/^#+/, '');
			elErrorContainer = elErrorContainer.replace(/^#+/, '');
			
			const successEl = document.getElementById(elSuccessContainer);
			const errorEl = document.getElementById(elErrorContainer);
			
			if (successEl) {
				successEl.innerHTML = '';
				successEl.style.display = 'none';
			}
			
			if (errorEl) {
				errorEl.innerHTML = '<ul class="mb-0 list-unstyled"></ul>';
				errorEl.style.display = 'none';
				errorEl.classList.add('alert', 'alert-block', 'alert-danger');
				errorEl.querySelector('ul').innerHTML = message;
				fadeIn(errorEl, 'fast'); {{-- 'fast' corresponds to 200ms, 'slow' to 600ms --}}
			}
		}
		
		/**
		 * Fade in an element
		 * @param {HTMLElement} element
		 * @param {string} speed
		 */
		function fadeIn(element, speed) {
			const duration = speed === 'fast' ? 200 : (speed === 'slow' ? 600 : 200);
			element.style.opacity = '0';
			element.style.display = 'block';
			
			let last = +new Date();
			const tick = () => {
				const newOpacity = +element.style.opacity + (new Date() - last) / duration;
				element.style.opacity = newOpacity.toString();
				last = +new Date();
				
				if (newOpacity < 1) {
					(window.requestAnimationFrame && requestAnimationFrame(tick)) || setTimeout(tick, 16);
				}
			};
			
			tick();
		}
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		var elSuccessContainer = '{{ $elSuccessContainer }}';
		var elErrorContainer = '{{ $elErrorContainer }}';
		var reorderUrl = '{{ $reorderUrl }}';
		
		{{-- fileinput Options --}}
		var fiOptions = {};
		fiOptions.theme = '{{ $theme }}';
		fiOptions.language = '{{ $language }}';
		fiOptions.rtl = {{ $rtl }};
		fiOptions.showClose = true;
		fiOptions.showUpload = false;
		fiOptions.showRemove = false;
		fiOptions.showCancel = false;
		fiOptions.showCaption = false; {{-- input field --}}
		fiOptions.showBrowse = true;
		fiOptions.browseClass = '{{ $browseClass }}';
		fiOptions.browseOnZoneClick = true;
		
		fiOptions.uploadUrl = '{{ $uploadUrl }}';
		fiOptions.uploadAsync = false;
		fiOptions.uploadExtraData = {!! collect($uploadExtraData)->toJson() !!};
		fiOptions.elErrorContainer = elErrorContainer;
		fiOptions.msgErrorClass = '{{ $msgErrorClass }}';
		
		fiOptions.showPreview = true;
		fiOptions.overwriteInitial = false;
		fiOptions.initialPreviewAsData = true;
		fiOptions.initialPreviewFileType = 'image';
		fiOptions.allowedFileExtensions = {!! collect($allowedFileExtensions)->toJson() !!};
		fiOptions.minFileSize = {{ (int)$minFileSize }};
		fiOptions.maxFileSize = {{ (int)$maxFileSize }};
		fiOptions.minFileCount = 0;
		fiOptions.maxFileCount = {{ $limit }};
		fiOptions.validateInitialCount = true;
		
		fiOptions.fileActionSettings = {
			showDrag: true,
			showUpload: false,
			showRotate: false,
			showRemove: {{ $showRemove }},
			showZoom: {{ $showZoom }},
			removeClass: '{{ $removeClass }}',
			zoomClass: '{{ $zoomClass }}',
		};
		
		fiOptions.initialPreview = [];
		fiOptions.initialPreviewConfig = [];
		
		@if (!empty($value))
			@php
				$idx = 0; // Need to be started by 0 to avoid reorder issue
			@endphp
			@foreach($value as $index => $file)
				@if (!empty($file) && is_array($file))
					@php
						$key = castToString($file['key'] ?? $defaultKey, $defaultKey);
						$filePath = castToStringOrNull($file['path'] ?? null);
						$fileUrl = castToStringOrNull($file['url'] ?? null);
					@endphp
					@if (!empty($filePath) && Storage::disk($diskName)->exists($filePath))
						@php
							if (empty($fileUrl)) {
								// $fileUrl = thumbParam($filePath)->setOption('picture-md')->url();
								// $fileUrl = hasTemporaryPath($filePath) ? rescue(fn () => Storage::disk($diskName)->url($filePath)) : $fileUrl;
								$fileUrl = thumbService($filePath)->resize('picture-md')->url();
							}
							$fileSize = rescue(fn () => Storage::disk($diskName)->size($filePath), 0);
							$fileUrl = $fileUrl ?? $defaultFileUrl;
							$deleteUrl = str_replace(['{index}', '{id}', '{key}'], $key, $deleteUrlPattern);
						@endphp
						fiOptions.initialPreview[{{ $idx }}] = '{{ $fileUrl }}';
						fiOptions.initialPreviewConfig[{{ $idx }}] = {};
						fiOptions.initialPreviewConfig[{{ $idx }}].key = {{ (int)$key }};
						fiOptions.initialPreviewConfig[{{ $idx }}].caption = '{{ basename($filePath) }}';
						fiOptions.initialPreviewConfig[{{ $idx }}].size = {{ $fileSize }};
						fiOptions.initialPreviewConfig[{{ $idx }}].url = '{{ $deleteUrl }}';
						@php
							$idx++; // The indexes must follow each other (i.e.: must be consecutive) to avoid eventual issues
						@endphp
					@endif
				@endif
			@endforeach
		@endif
		
		onDocumentReady((event) => {
			{{-- fileinput --}}
			const dropzoneFieldEl = $('#dz_{{ $id }}');
			dropzoneFieldEl.fileinput(fiOptions);
			
			{{-- Before upload hook --}}
			dropzoneFieldEl.on('filebatchpreupload', (event, data) => {
				{{-- Empty & hide the success element container --}}
				const uploadSuccessEl = $(elSuccessContainer);
				if (uploadSuccessEl.length) {
					uploadSuccessEl.html('<ul class="mb-0 list-unstyled"></ul>').hide();
				}
			});
			
			{{-- File selected hook (from "Browse" button or drag-n-drop) --}}
			dropzoneFieldEl.on('filebatchselected', (event, files) => {
				{{-- Auto upload the selected file --}}
				$(event.target).fileinput('upload');
			});
			
			{{-- After successful upload hook --}}
			dropzoneFieldEl.on('filebatchuploadsuccess', (event, data) => {
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
				let uploadSuccessEl = $(elSuccessContainer);
				if (uploadSuccessEl.length) {
					uploadSuccessEl.find('ul').append(message);
					uploadSuccessEl.fadeIn('slow');
				}
				
				{{-- Hide the progress bar after delay (in milliseconds) --}}
				const delay = 1000;
				setTimeout(() => {
					const progressBarEl = $('.kv-upload-progress.kv-hidden');
					if (progressBarEl.length) {
						progressBarEl.hide();
					}
				}, delay);
				
				{{-- Change button label --}}
				const nextStepActionEl = $('#nextStepAction');
				if (nextStepActionEl.length) {
					nextStepActionEl.html('{!! $nextStepLabel !!}');
					nextStepActionEl.removeClass('btn-secondary');
					nextStepActionEl.removeClass('btn-outline-primary').addClass('btn-primary');
				}
			});
			
			{{-- After error occured during file batch upload hook --}}
			dropzoneFieldEl.on('filebatchuploaderror', (event, data, errorMessage) => {
				showErrorMessage(errorMessage, elSuccessContainer, elErrorContainer);
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
			dropzoneFieldEl.on('filebeforedelete', async (event, key, data) => {
				const deleteFileConfirmQuestion = "{{ t('confirm_picture_deletion') }}";
				const confirmedAction = await swalConfirm(deleteFileConfirmQuestion);
				return !confirmedAction;
			});
			
			{{-- After deletion hook --}}
			dropzoneFieldEl.on('filedeleted', (event, key, jqXHR, data) => {
				if (typeof jqXHR.responseJSON === 'undefined') {
					return false;
				}
				
				let obj = jqXHR.responseJSON;
				if (typeof obj.status === 'undefined' || typeof obj.message === 'undefined') {
					return false;
				}
				
				{{-- Deletion Notification --}}
				if (parseInt(obj.status) === 1) {
					showSuccessMessage(obj.message, elSuccessContainer, elErrorContainer);
				} else {
					showErrorMessage(obj.message, elSuccessContainer, elErrorContainer);
				}
			});
			
			{{-- After error occured during file deletion hook --}}
			dropzoneFieldEl.on('filedeleteerror', (event, data, errorMessage) => {
				showErrorMessage(errorMessage, elSuccessContainer, elErrorContainer);
			});
			
			{{-- After files sorted (on browser) hook --}}
			dropzoneFieldEl.on('filesorted', (event, params) => {
				dzReorderFiles(params, reorderUrl, elSuccessContainer, elErrorContainer);
			});
			
			{{-- Triggered when preview images are clicked --}}
			{{-- Zoom clicked previewed image --}}
			{{-- Preview Selector: .file-drop-zone .kv-file-content img.file-preview-image --}}
			const thumbnailsImgSelector = '.file-drop-zone .kv-file-content img.file-preview-image';
			$(document).on('click', thumbnailsImgSelector, function () {
				const thumbnailEl = this.closest('.file-preview-frame');
				if (thumbnailEl) {
					dropzoneFieldEl.fileinput('zoom', $(thumbnailEl).attr('id'));
				}
			});
		});
	</script>
@endpush
