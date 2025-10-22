@php
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Number;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'image';
	$type = 'image';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$attributes ??= [];
	
	$pluginOptions ??= [];
	
	$disk = $pluginOptions['disk'] ?? null;
	$imgWidth = $pluginOptions['imgWidth'] ?? 'auto';
	$imgHeight = $pluginOptions['imgHeight'] ?? 'auto';
	$aspectRatio = $pluginOptions['aspectRatio'] ?? 0;
	$crop = $pluginOptions['crop'] ?? false;
	$prefixUrl = $pluginOptions['prefixUrl'] ?? '';
	
	$disk ??= null;
	$imgWidth ??= 'auto';
	$imgHeight ??= 'auto';
	$aspectRatio ??= 0;
	$crop ??= false;
	$prefixUrl ??= '';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	// $value = $value ?? ($default ?? null);
	// $value = old($name, $value);
	
	$diskName = $disk ?? null;
	
	// Get default picture & its URL
	$defaultPicture = config('larapen.media.picture');
	$defaultPictureUrl = Storage::disk($diskName)->url($defaultPicture);
	
	// Get default value (Need to be sent/filled as URL)
	$defaultValue = $default ?? null;
	$defaultValue = (!empty($defaultValue) && is_string($defaultValue)) ? $defaultValue : $defaultPictureUrl;
	
	// Get value (Sent/filled as storage path)
	$value = $value ?? null;
	
	// Get the picture's URL
	$pictureUrl = (!empty($value) && is_string($value)) ? Storage::disk($diskName)->url($value) : $defaultValue;
	// $pictureUrl = is_string($value) ? thumbParam($value)->setOption('picture-md')->url() : $defaultValue;
	$pictureUrl = old($dotSepName, $pictureUrl);
	
	// Get the picture's URL with prefix path (If filled)
	$pictureUrl = $prefixUrl . $pictureUrl;
	
	// Get picture display dimensions
	if (is_numeric($imgWidth)) {
		$imgWidth = Number::clamp($imgWidth, min: 100, max: 800);
	}
	if (is_numeric($imgHeight)) {
		$imgHeight = Number::clamp($imgHeight, min: 100, max: 800);
	}
	$imgWidth = is_numeric($imgWidth) ? $imgWidth . 'px' : $imgWidth;
	$imgHeight = is_numeric($imgHeight) ? $imgHeight . 'px' : $imgHeight;
	
	// Dimensions style
	$dimensionStyle = (str_ends_with($imgWidth, 'px') ? 'max-' : '')  . 'width:' . $imgWidth . ';';
	$dimensionStyle .= (str_ends_with($imgHeight, 'px') ? 'max-' : '') . 'height:' . $imgHeight . ';';
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'hide');
@endphp
<div
		data-preview="#{{ $id }}"
		data-aspectRatio="{{ $aspectRatio }}"
		data-crop="{{ $crop }}"
		@include('helpers.forms.attributes.field-wrapper')
>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<div class="col-12 text-center p-2 border border-1 border-light rounded-2" style="height: 100%;">
				{{-- Wrap the image or canvas element with a block element (container) --}}
				<div class="row d-flex justify-content-center mt-3 mb-3">
					<div class="col-sm-6 text-center">
						<img id="mainImage" src="{{ url($pictureUrl) }}" alt="" class="rounded" style="{!! $dimensionStyle !!}">
					</div>
					@if ($crop)
						<div class="col-sm-3 text-center">
							<div class="docs-preview clearfix">
								<div id="{{ $id }}" class="img-preview preview-lg">
									<img
											src=""
											alt=""
											style="display: block; min-width: 0 !important; min-height: 0 !important; max-width: none !important; max-height: none !important; margin-left: -32.875px; margin-top: -18.4922px; transform: none;"
									>
								</div>
							</div>
						</div>
					@endif
				</div>
				
				<div class="btn-group">
					<label class="btn btn-primary btn-file mb-0">
						{{ trans('admin.choose_file') }}
						<input
								type="file"
								id="uploadImage"
								accept="image/*"
								@include('helpers.forms.attributes.field')
						>
						<input type="hidden" id="hiddenImage" name="{{ $name }}">
					</label>
					@if ($crop)
						<button class="btn btn-secondary" id="rotateLeft" type="button" style="display: none;">
							<i class="fa-solid fa-rotate-left"></i>
						</button>
						<button class="btn btn-secondary" id="rotateRight" type="button" style="display: none;">
							<i class="fa-solid fa-rotate-right"></i>
						</button>
						<button class="btn btn-secondary" id="zoomIn" type="button" style="display: none;">
							<i class="fa-solid fa-magnifying-glass-plus"></i>
						</button>
						<button class="btn btn-secondary" id="zoomOut" type="button" style="display: none;">
							<i class="fa-solid fa-magnifying-glass-minus"></i>
						</button>
						<button class="btn btn-warning" id="reset" type="button" style="display: none;">
							<i class="fa-solid fa-xmark"></i>
						</button>
					@endif
					<button class="btn btn-danger" id="remove" type="button">
						<i class="fa-regular fa-trash-can"></i>
					</button>
				</div>
				
				@include('helpers.forms.partials.hint')
				@include('helpers.forms.partials.validation')
			</div>
			
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
		
		.image .btn-group {
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
			$('form div.image').each(function (index) {
				// Find DOM elements under this form-group element
				const $mainImage = $(this).find('#mainImage');
				const $uploadImage = $(this).find("#uploadImage");
				const $hiddenImage = $(this).find("#hiddenImage");
				const $rotateLeft = $(this).find("#rotateLeft")
				const $rotateRight = $(this).find("#rotateRight")
				const $zoomIn = $(this).find("#zoomIn")
				const $zoomOut = $(this).find("#zoomOut")
				const $reset = $(this).find("#reset")
				const $remove = $(this).find("#remove")
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
						$rotateLeft.hide();
						$rotateRight.hide();
						$zoomIn.hide();
						$zoomOut.hide();
						$reset.hide();
						$remove.hide();
					});
				} else {
					$(this).find("#remove").click(function () {
						$mainImage.attr('src', '');
						$hiddenImage.val('');
						$remove.hide();
					});
				}
				
				$uploadImage.change(function () {
					let fileReader = new FileReader(),
						files = this.files,
						file;
					
					if (!files.length) {
						return;
					}
					file = files[0];
					
					if (/^image\/\w+$/.test(file.type)) {
						fileReader.readAsDataURL(file);
						fileReader.onload = function () {
							$uploadImage.val("");
							if (crop) {
								$mainImage.cropper(options).cropper("reset", true).cropper("replace", this.result);
								// Override form submit to copy canvas to hidden input before submitting
								$('form').submit(function () {
									const imageURL = $mainImage.cropper('getCroppedCanvas').toDataURL(file.type);
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
