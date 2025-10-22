@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'base64-image';
	$type = 'file';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$pluginOptions ??= [];
	
	$filename = $pluginOptions['filename'] ?? null;
	$aspectRatio = $pluginOptions['aspectRatio'] ?? 0;
	$crop = $pluginOptions['crop'] ?? false;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($name, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'hide');
@endphp
<div class="mb-3 col-md-12 image"
     data-preview="#{{ $name }}"
     data-aspectRatio="{{ $aspectRatio }}"
     data-crop="{{ $crop }}"
		@include('helpers.forms.attributes.field-wrapper')
>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			{{-- Wrap the image or canvas element with a block element (container) --}}
			<div class="row">
				<div class="col-sm-6 mb-3">
					<img id="mainImage" src="{{ $value }}" alt="">
				</div>
				@if ($crop)
					<div class="col-sm-3">
						<div class="docs-preview clearfix">
							<div id="{{ $name }}" class="img-preview preview-lg">
								@php
									$imgStyle = 'display: block;';
									$imgStyle .= 'min-width: 0px !important;';
									$imgStyle .= 'min-height: 0px !important;';
									$imgStyle .= 'max-width: none !important;';
									$imgStyle .= 'max-height: none !important;';
									$imgStyle .= 'margin-left: -32.875px;';
									$imgStyle .= 'margin-top: -18.4922px;';
									$imgStyle .= 'transform: none;';
								@endphp
								<img src="" style="{!! $imgStyle !!}" alt="">
							</div>
						</div>
					</div>
				@endif
				<input type="hidden" id="hiddenFilename" name="{{ $filename }}" value="">
			</div>
			<div class="btn-group">
				<label class="btn btn-primary btn-file">
					Choose file
					<input type="file" accept="image/*" id="uploadImage" @include('helpers.forms.attributes.field')>
					<input type="hidden" id="hiddenImage" name="{{ $name }}">
				</label>
				@if ($crop)
					<button class="btn btn-secondary" id="rotateLeft" type="button" style="display: none;"><i class="fa-solid fa-rotate-left"></i></button>
					<button class="btn btn-secondary" id="rotateRight" type="button" style="display: none;"><i class="fa-solid fa-rotate-right"></i></button>
					<button class="btn btn-secondary" id="zoomIn" type="button" style="display: none;"><i class="fa-solid fa-magnifying-glass-plus"></i>
					</button>
					<button class="btn btn-secondary" id="zoomOut" type="button" style="display: none;"><i class="fa-solid fa-magnifying-glass-minus"></i>
					</button>
					<button class="btn btn-warning" id="reset" type="button" style="display: none;"><i class="fa-solid fa-xmark"></i></button>
				@endif
				<button class="btn btn-danger" id="remove" type="button"><i class="fa-regular fa-trash-can"></i></button>
			</div>
			
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

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_helper_styles")
	<link href="{{ asset('assets/plugins/cropper/dist/cropper.min.css') }}" rel="stylesheet" type="text/css"/>
	<style>
		.hide {
			display: none;
		}
		
		.btn-group {
			margin-top: 10px;
		}
		
		img {
			max-width: 100%; /* This rule is very important, please do not ignore this! */
		}
		
		.img-container, .img-preview {
			width: 100%;
			text-align: center;
		}
		
		.img-preview {
			float: left;
			margin-right: 10px;
			margin-bottom: 10px;
			overflow: hidden;
		}
		
		.preview-lg {
			width: 263px;
			height: 148px;
		}
		
		.btn-file {
			position: relative;
			overflow: hidden;
		}
		
		.btn-file input[type=file] {
			position: absolute;
			top: 0;
			right: 0;
			min-width: 100%;
			min-height: 100%;
			font-size: 100px;
			text-align: right;
			filter: alpha(opacity=0);
			opacity: 0;
			outline: none;
			background: white;
			cursor: inherit;
			display: block;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_helper_scripts")
	<script src="{{ asset('assets/plugins/cropper/dist/cropper.min.js') }}"></script>
	<script>
		onDocumentReady((event) => {
			// Loop through all instances of the image field
			$('.input-group.image').each(function (index) {
				// Find DOM elements under this input-group element
				const $mainImage = $(this).find('#mainImage');
				const $uploadImage = $(this).find("#uploadImage");
				const $hiddenImage = $(this).find("#hiddenImage");
				const $hiddenFilename = $(this).find("#hiddenFilename");
				const $rotateLeft = $(this).find("#rotateLeft");
				const $rotateRight = $(this).find("#rotateRight");
				const $zoomIn = $(this).find("#zoomIn");
				const $zoomOut = $(this).find("#zoomOut");
				const $reset = $(this).find("#reset");
				const $remove = $(this).find("#remove");
				
				// Options either global for all image type fields, or use 'data-*' elements for options passed in via the CRUD controller
				const options = {
					viewMode: 2,
					checkOrientation: false,
					autoCropArea: 1,
					responsive: true,
					preview: $(this).attr('data-preview'),
					aspectRatio: $(this).attr('data-aspectRatio')
				};
				
				const crop = $(this).attr('data-crop');
				
				// Hide 'Remove' button if there is no image saved
				if (!$mainImage.attr('src')) {
					$remove.hide();
				}
				// Initialise hidden form input in case we submit with no change
				$hiddenImage.val($mainImage.attr('src'));
				
				// Only initialize cropper plugin if crop is set to true
				if (crop) {
					$remove.click(function () {
						$mainImage.cropper("destroy");
						$mainImage.attr('src', '');
						$hiddenImage.val('');
						if (filename === "true") {
							$hiddenFilename.val('removed');
						}
						$rotateLeft.hide();
						$rotateRight.hide();
						$zoomIn.hide();
						$zoomOut.hide();
						$reset.hide();
						$remove.hide();
					});
				} else {
					$(this).find('#remove').click(function () {
						$mainImage.attr('src', '');
						$hiddenImage.val('');
						$hiddenFilename.val('removed');
						$remove.hide();
					});
				}
				
				// Set hiddenFilename field to 'removed' if image has been removed.
				// Otherwise, hiddenFilename will be null if no changes have been made.
				
				$uploadImage.change(function () {
					let fileReader = new FileReader(),
						files = this.files,
						file;
					
					if (!files.length) {
						return;
					}
					file = files[0];
					
					if (/^image\/\w+$/.test(file.type)) {
						$hiddenFilename.val(file.name);
						fileReader.readAsDataURL(file);
						fileReader.onload = function () {
							$uploadImage.val("");
							if (crop) {
								$mainImage.cropper(options).cropper("reset", true).cropper("replace", this.result);
								// Override form submit to copy canvas to hidden input before submitting
								$('form').submit(function () {
									var imageURL = $mainImage.cropper('getCroppedCanvas').toDataURL();
									$hiddenImage.val(imageURL);
									return true; // return false to cancel form action
								});
								$rotateLeft.click(function () {
									$mainImage.cropper("rotate", 90);
								});
								$rotateRight.click(function () {
									$mainImage.cropper("rotate", -90);
								});
								$zoomIn.click(function () {
									$mainImage.cropper("zoom", 0.1);
								});
								$zoomOut.click(function () {
									$mainImage.cropper("zoom", -0.1);
								});
								$reset.click(function () {
									$mainImage.cropper("reset");
								});
								$rotateLeft.show();
								$rotateRight.show();
								$zoomIn.show();
								$zoomOut.show();
								$reset.show();
								$remove.show();
							} else {
								$mainImage.attr('src', this.result);
								$hiddenImage.val(this.result);
								$remove.show();
							}
						};
					} else {
						alert("Please choose an image file.");
					}
				});
				
			});
		});
	</script>
@endpushonce
