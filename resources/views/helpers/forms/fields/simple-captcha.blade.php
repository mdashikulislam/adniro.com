{{-- simple-captcha --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'simple-captcha';
	$type = 'text';
	$label ??= null; // trans('auth.captcha_human_verification')
	$id ??= null;
	$name = 'captcha';
	$placeholder ??= t('captcha_placeholder');
	$hint = t('captcha_hint');
	$wrapper ??= []; // Wrapper attributes (including "class")
	
	$id = !empty($id) ? $id : $name;
	
	$wrapperBaseClass = ($isHorizontal ? 'mb-3 row' : 'mb-3 col-md-12');
	$captchaSelectorBase = 'captcha-div';
	$wrapperClass = $wrapper['class'] ?? "$wrapperBaseClass $captchaSelectorBase";
	$wrapperClass = !str_contains($wrapperClass, $captchaSelectorBase) ? "$wrapperClass $captchaSelectorBase" : $wrapperClass;
	$wrapper['class'] = $wrapperClass;
	
	$pluginOptions ??= [];
	
	$captchaType = config('settings.security.captcha');
	$delayToDisplay = (int)($pluginOptions['delayToDisplay'] ?? config('settings.security.captcha_delay', 1000));
	$reloadUrl = $pluginOptions['reloadUrl'] ?? url('captcha/' . $captchaType);
	$blankImage = $pluginOptions['blankImage'] ?? url('images/blank.gif');
	$captchaWrapperWidth = 256;
	$defaultCaptchaWidth = config('captcha.' . $captchaType . '.width', 150); // 150
	$captchaWidth = (int)($pluginOptions['width'] ?? $defaultCaptchaWidth);
	
	$isSimpleCaptchaEnabled = (
		in_array($captchaType, ['default', 'math', 'flat', 'mini', 'inverse', 'custom'])
		&& !empty(config('captcha.option'))
	);
	
	if ($isSimpleCaptchaEnabled) {
		$captchaWrapperWidthCss = "width: {$captchaWrapperWidth}px;";
		$captchaWrapperStyleCss = ' style="' . $captchaWrapperWidthCss . '"';
		$captchaWidthCss = "width: {$captchaWidth}px;";
		$captchaStyleCss = ' style="' . $captchaWidthCss . '"';
		$captchaUrl = captcha_src($captchaType);
		$imgClass = ($captchaType == 'mini') ? '' : 'w-100 me-auto ';
		$captchaImage = '<img src="' . $blankImage . '" class="' . $imgClass . 'rounded" style="vertical-align: middle;">';
		$captchaSpinner = '<div class="captcha-spinner w-100 h-100 py-2 d-inline-flex justify-content-center align-items-center me-auto rounded"' . $captchaStyleCss . '>';
		$captchaSpinner .= '<div class="spinner-border" role="status">';
		$captchaSpinner .= '<span class="visually-hidden">Loading...</span>';
		$captchaSpinner .= '</div>';
		$captchaSpinner .= '</div>';
		
		// DEBUG
		// The generated key need to be un-hashed before to be stored in session
		// dump(session('captcha.key'));
	}
	
	$reloadTitle = t('captcha_reload_hint');
@endphp
@if ($isSimpleCaptchaEnabled)
	<div @include('helpers.forms.attributes.field-wrapper')>
		@include('helpers.forms.partials.label')
		
		<div class="{{ $isHorizontal ? $colField : '' }}">
			<div class="hstack gap-1"{!! $captchaWrapperStyleCss !!}>
				<div class="captcha-challenge w-100 border rounded d-inline-flex justify-content-center align-items-center" style="min-height: 58px;"></div>
				<a href="" class="btn btn-primary btn-refresh py-2 px-3" data-bs-toggle="tooltip" title="{{ $reloadTitle }}" rel="nofollow">
					<i class="fa-solid fa-rotate"></i>
				</a>
			</div>
			
			@if (!empty($hint))
				<div class="form-text my-1">{!! $hint !!}</div>
			@endif
			
			<div class="captcha-input"{!! $captchaWrapperStyleCss !!}>
				<input
						type="text"
						name="{{ $name }}"
						autocomplete="off"
						@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
						@include('helpers.forms.attributes.field')
				>
			</div>
			
			@include('helpers.forms.partials.validation')
		</div>
	</div>
	@include('helpers.forms.partials.newline')
@endif

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_scripts")
	@if ($isSimpleCaptchaEnabled)
		<script>
			/* Load the captcha image when the page is loaded */
			function loadCaptchaImage(captchaChallengeSelector, captchaUrl, captchaSpinnerHtml, captchaImageHtml) {
				captchaUrl = getTimestampedUrl(captchaUrl);
				
				/* Convert captcha image HTML to a DOM object */
				const captchaImageTmpEl = createElementFromHtml(captchaImageHtml);
				captchaImageTmpEl.src = captchaUrl;
				
				const captchaImgSelector = `${captchaChallengeSelector} img`;
				
				/* Remove existing <img> */
				let captchaImageEls = document.querySelectorAll(captchaImgSelector);
				if (captchaImageEls.length > 0) {
					captchaImageEls.forEach(element => element.remove());
				}
				
				/* Add the <img> tag in the DOM */
				const captchaChallengeEls = document.querySelectorAll(captchaChallengeSelector);
				if (captchaChallengeEls.length > 0) {
					captchaChallengeEls.forEach(element => {
						/* element.append(captchaImageTmpEl); // ❌ Only works once */
						element.append(captchaImageTmpEl.cloneNode(true));
						
						/* Show the spinner */
						insertCaptchaSpinner(captchaChallengeSelector, captchaSpinnerHtml);
					});
				}
				
				/* Handle the added images */
				/* Show the captcha's added images only when their image files are fully loaded */
				const captchaObserver = domObserverBatch('body', {
					onAdd: (addedElements) => {
						/* Process all added elements at once */
						const captchaImages = [];
						
						addedElements.forEach(element => {
							if (element.matches(captchaImgSelector)) {
								captchaImages.push(element);
							}
							captchaImages.push(...element.querySelectorAll(captchaImgSelector));
						});
						
						/* Add handlers to all new captcha images */
						captchaImages.forEach(img => {
							img.addEventListener('load', () => {
								/* Hide the spinner */
								removeCaptchaSpinner(captchaChallengeSelector, img);
							});
							
							img.addEventListener('error', () => {
								console.error('Error loading captcha image');
								
								/* Still hide the spinner even if loading fails */
								removeCaptchaSpinner(captchaChallengeSelector, img);
							});
						});
					},
					debounce: 100 /* Optional debounce for rapid additions */
				});
				
				/* Handle the existing images */
				/* Show the existing captcha's images only when their image files are fully loaded */
				captchaImageEls = document.querySelectorAll(captchaImgSelector);
				if (captchaImageEls.length > 0) {
					captchaImageEls.forEach(img => {
						img.addEventListener('load', () => {
							/* Hide the spinner */
							removeCaptchaSpinner(captchaChallengeSelector, img);
						});
						
						img.addEventListener('error', () => {
							console.error('Error loading captcha image');
							
							/* Still hide the spinner even if loading fails */
							removeCaptchaSpinner(captchaChallengeSelector, img);
						});
					});
				}
			}
			
			/* Set up the CAPTCHA reload by clicking on the CAPTCHA link */
			function setupCaptchaReloadByClickingOnLink(captchaChallengeSelector, captchaUrl, captchaSpinnerHtml, captchaImgSelector, captchaLinkSelector) {
				const captchaLinkEls = document.querySelectorAll(captchaLinkSelector);
				if (captchaLinkEls.length <= 0) return;
				
				captchaLinkEls.forEach(link => {
					link.addEventListener('click', e => {
						e.preventDefault();
						
						const captchaImageEls = document.querySelectorAll(captchaImgSelector);
						if (captchaImageEls.length > 0) {
							captchaImageEls.forEach(img => {
								reloadCaptchaImage(captchaChallengeSelector, img, captchaUrl, captchaSpinnerHtml);
							});
						}
					});
				});
			}
			
			/* Helper function to add the click handler */
			function addCaptchaClickHandler(captchaChallengeSelector, element, captchaUrl, captchaSpinnerHtml) {
				if (!element) return;
				
				element.addEventListener('click', e => {
					e.preventDefault();
					reloadCaptchaImage(captchaChallengeSelector, e.target, captchaUrl, captchaSpinnerHtml);
				});
			}
			
			// Reload the CAPTCHA image */
			function reloadCaptchaImage(captchaChallengeSelector, captchaImageEl, captchaUrl, captchaSpinnerHtml) {
				/* Show the spinner */
				insertCaptchaSpinner(captchaChallengeSelector, captchaSpinnerHtml);
				
				captchaUrl = getTimestampedUrl(captchaUrl);
				captchaImageEl.src = captchaUrl;
				
				/* Create a temporary image to preload */
				const tmpImg = new Image();
				tmpImg.addEventListener('load', () => {
					/* Hide the spinner */
					removeCaptchaSpinner(captchaChallengeSelector);
				});
				tmpImg.addEventListener('error', () => {
					console.error('Failed to load CAPTCHA image:', captchaUrl);
					
					/* Still hide the spinner even if loading fails */
					removeCaptchaSpinner(captchaChallengeSelector);
				});
				
				/* Start loading the new image */
				tmpImg.src = captchaUrl;
			}
			
			/* Insert spinner into all the "div.captcha-challenge" elements */
			function insertCaptchaSpinner(captchaChallengeSelector, captchaSpinnerHtml) {
				const captchaSpinnerTmpEl = createElementFromHtml(captchaSpinnerHtml);
				
				const captchaDivs = document.querySelectorAll(captchaChallengeSelector);
				
				captchaDivs.forEach(div => {
					/* Hide the image */
					const imageEl = div.querySelector('img');
					if (imageEl) {
						imageEl.style.display = 'none';
					}
					
					/* Check if spinner already exists to avoid duplicates */
					if (!div.querySelector('.captcha-spinner')) {
						div.append(captchaSpinnerTmpEl);
					}
				});
			}
			
			/* Remove all inserted spinners */
			function removeCaptchaSpinner(captchaChallengeSelector, imageEl = null) {
				/* Show captcha images */
				if (imageEl) {
					imageEl.style.display = '';
				} else {
					const captchaImgSelector = `${captchaChallengeSelector} img`;
					const captchaImages = document.querySelectorAll(captchaImgSelector);
					captchaImages.forEach(img => {
						img.style.display = '';
					});
				}
				
				/* Remove captcha spinners */
				const spinners = document.querySelectorAll(`${captchaChallengeSelector} .captcha-spinner`);
				spinners.forEach(spinner => {
					spinner.remove();
				});
			}
			
			/* Make the CAPTCHA URL unique by adding the current timestamp to its parameters */
			function getTimestampedUrl(captchaUrl) {
				if (captchaUrl.indexOf('?') !== -1) {
					return captchaUrl;
				}
				const timestamp = new Date().getTime();
				
				return urlBuilder(captchaUrl, true).setParameters({t: timestamp}).value();
			}
			
			/* Create a DOM element from an HTML string */
			function createElementFromHtml(htmlString) {
				/* Create a new DOMParser instance */
				const parser = new DOMParser();
				
				/* Parse the string into a Document */
				const doc = parser.parseFromString(htmlString, 'text/html');
				
				/* Extract the first child (usually the parsed element) */
				return doc.body.firstChild;
			}
		</script>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	@if ($isSimpleCaptchaEnabled)
		<script>
			onDocumentReady((event) => {
				const captchaSelectorBase = '.{{ $captchaSelectorBase }}';
				const captchaChallengeSelector = `${captchaSelectorBase} div.captcha-challenge`;
				const captchaImgSelector = `${captchaChallengeSelector} img`;
				const captchaUrl = '{{ $reloadUrl }}';
				const captchaImageHtml = '{!! $captchaImage !!}';
				const captchaSpinnerHtml = '{!! $captchaSpinner !!}';
				
				/* Load the captcha image */
				{{--
				 * Load the captcha image N ms after the page is loaded
				 *
				 * Admin panel: 0ms
				 * Front:
				 * Chrome: 600ms
				 * Edge: 600ms
				 * Safari: 500ms
				 * Firefox: 100ms
				--}}
				const stTimeout = {{ $delayToDisplay }};
				setTimeout(() => loadCaptchaImage(captchaChallengeSelector, captchaUrl, captchaSpinnerHtml, captchaImageHtml), stTimeout);
				
				/*
				 * Handle captcha reload link click
				 * Reload the captcha image on by clicking on the reload link
				 */
				const captchaLinkSelector = `${captchaSelectorBase} a.btn-refresh`;
				setupCaptchaReloadByClickingOnLink(captchaChallengeSelector, captchaUrl, captchaSpinnerHtml, captchaImgSelector, captchaLinkSelector);
			});
		</script>
	@endif
@endpush
