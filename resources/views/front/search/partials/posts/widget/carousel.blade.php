@php
	$widget ??= [];
	$posts = (array)data_get($widget, 'posts');
	$totalPosts = (int)data_get($widget, 'totalPosts', 0);
	
	$sectionOptions ??= [];
	
	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';
	
	$htmlAttr = $sectionOptions['html_attributes'] ?? '';
	$htmlAttr = !empty($htmlAttr) ? " {$htmlAttr}" : '';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
	
	// Carousel Options
	$langDirection = config('lang.direction');
	$isRTLEnabled = ($langDirection == 'rtl');
	$otherSettings = (array)config('settings.other');
	$carouselSlideBy = (data_get($otherSettings, 'carousel_slide_by_page') == '1') ? 'page' : 1; // Positive number OR 'page'
	$carouselMouseDrag = (data_get($otherSettings, 'carousel_mouse_drag') == '1') ? 'true' : 'false';
	$carouselLoop = (data_get($otherSettings, 'carousel_loop') == '1') ? 'true' : 'false';
	$carouselRewind = (data_get($otherSettings, 'carousel_rewind') == '1') ? 'true' : 'false';
	$carouselAutoplay = (data_get($otherSettings, 'carousel_autoplay') == '1') ? 'true' : 'false';
	$carouselAutoplayTimeout = (int)(data_get($otherSettings, 'carousel_autoplay_timeout') ?? 1500);
	$carouselAutoplayHoverPause = (data_get($otherSettings, 'carousel_autoplay_hover_pause') == '1') ? 'true' : 'false';
	$carouselNav = (data_get($otherSettings, 'carousel_nav') == '1') ? 'true' : 'false';
	$carouselNavPosition = data_get($otherSettings, 'carousel_nav_position') ?? 'bottom';
	$carouselCtrl = (data_get($otherSettings, 'carousel_controls') == '1') ? 'true' : 'false';
	$carouselCtrlPosition = data_get($otherSettings, 'carousel_ctrl_position') ?? 'top-end';
	
	// Carousel Data Validation
	$carouselNavPositions = (array)config('larapen.options.carousel.navPositions');
	$carouselCtrlPositions = (array)config('larapen.options.carousel.ctrlPositions');
	$carouselSlideBy = (is_numeric($carouselSlideBy) || $carouselSlideBy === 'page') ? $carouselSlideBy : 1;
	$carouselSlideBy = is_numeric($carouselSlideBy) ? (int) $carouselSlideBy : $carouselSlideBy;
	$carouselCtrlPosition = in_array($carouselCtrlPosition, $carouselCtrlPositions) ? $carouselCtrlPosition : 'top-end';
	$carouselNavPosition = in_array($carouselNavPosition, $carouselNavPositions) ? $carouselNavPosition : 'bottom';
	
	// Carousel Other Variables
	$carouselSlug = 'carousel-' . createRandomString();
	$carouselCtrlContainerClass = 'carousel-controls';
	$carouselCtrlFlexClass = str_contains($carouselCtrlPosition, 'start') ? ' d-flex justify-content-start' : '';
	$carouselCtrlFlexClass = str_contains($carouselCtrlPosition, 'end') ? ' d-flex justify-content-end' : $carouselCtrlFlexClass;
	$carouselCtrlFlexClass = str_contains($carouselCtrlPosition, 'center') ? ' d-flex justify-content-center' : $carouselCtrlFlexClass;
	$carouselCtrlFlexClass = str_contains($carouselCtrlPosition, 'between') ? ' d-flex justify-content-between' : $carouselCtrlFlexClass;
	$carouselCtrlMarginClass = !str_contains($carouselCtrlPosition, 'middle') ? ' mb-2' : '';
	$carouselCtrlClass = $carouselCtrlFlexClass . $carouselCtrlMarginClass;
	$carouselCtrlBtnClass = !str_contains($carouselCtrlPosition, 'middle') ? ' btn-xs' : '';
	
	$isReviewsAddonInstalled = config('plugins.reviews.installed');
	$itemHeight = $isReviewsAddonInstalled ? 340 : 320;
	$itemStyle = ' style="height:' . $itemHeight . 'px;"';
	$titleClass = $isReviewsAddonInstalled ? ' fs-6 fw-bold' : ' fs-5';
	$titleLimit = $isReviewsAddonInstalled ? 48 : 42;
@endphp
@if ($totalPosts > 0)
	<div class="container{{ $cssClasses }}" style="{!! $style !!}">
		<div class="card"{!! $htmlAttr !!}>
			<div class="card-header border-bottom-0">
				<h4 class="mb-0 float-start fw-lighter">
					{!! data_get($widget, 'title') !!}
				</h4>
				<h5 class="mb-0 float-end mt-1 fs-6 fw-lighter text-uppercase">
					<a href="{{ data_get($widget, 'link') }}" class="{{ linkClass() }}">
						{{ t('View more') }} <i class="fa-solid fa-bars"></i>
					</a>
				</h5>
			</div>
			
			@php
				// Workaround: Force LTR direction for tiny-slider when RTL is enabled due to lack of native RTL support
				// Note: Remove when official RTL support is implemented.
				$ltrAttr = $isRTLEnabled ? ' dir="ltr"' : '';
			@endphp
			<div class="card-body rounded p-3"{!! $ltrAttr !!}>
				@if ($carouselCtrl == 'true' && !str_starts_with($carouselCtrlPosition, 'bottom'))
					@include('front.search.partials.posts.widget.carousel.controls')
				@endif
				<div class="m-0 featured-list-slider {{ $carouselSlug }} px-1">
					@foreach($posts as $key => $post)
						@php
							$postUrl = urlGen()->post($post);
						@endphp
						<div class="border-0">
							<div class="item card p-0 d-flex justify-content-between flex-column hover-bg-tertiary"{!! $itemStyle !!}>
								{{-- Main Picture --}}
								<div class="w-100 m-0 position-relative item-carousel-thumb">
									<div class="position-absolute top-0 end-0 mt-2 me-2 bg-body-secondary opacity-75 rounded p-1">
										<i class="fa-solid fa-camera"></i> {{ data_get($post, 'count_pictures') }}
									</div>
									<a href="{{ $postUrl }}" class="{{ linkClass('body-emphasis') }}">
										@php
											$src = data_get($post, 'picture.url.medium');
											$webpSrc = data_get($post, 'picture.url.webp.medium');
											$alt = str(data_get($post, 'title'))->slug();
											$attr = ['class' => 'lazyload img-fluid rounded-top'];
											echo generateImageHtml($src, $alt, $webpSrc, $attr);
										@endphp
									</a>
								</div>
								
								<div class="card-body h-100 d-flex justify-content-between flex-column">
									{{-- Title --}}
									<h6 class="mb-0{{ $titleClass }} px-0 text-center text-break">
										<a href="{{ $postUrl }}" class="{{ linkClass() }}">
											{{ str(data_get($post, 'title'))->limit($titleLimit) }}
										</a>
									</h6>
									
									<div class="d-flex flex-column">
										{{-- Reviews Stars --}}
										@if ($isReviewsAddonInstalled)
											<div class="text-center">
												@if (view()->exists('reviews::ratings-list'))
													@include('reviews::ratings-list')
												@endif
											</div>
										@endif
										
										{{-- Price --}}
										<h4 class="fs-4 fw-bold mt-3 text-center">
											{!! data_get($post, 'price_formatted') !!}
										</h4>
									</div>
								</div>
							</div>
						</div>
					@endforeach
				</div>
				@if ($carouselCtrl == 'true' && str_starts_with($carouselCtrlPosition, 'bottom'))
					@include('front.search.partials.posts.widget.carousel.controls')
				@endif
			</div>
		</div>
	</div>
@endif

@section('after_styles')
	@parent
	@if ($totalPosts > 0)
		<style>
			{{-- Carousel Controls Middle Position --}}
			@if ($carouselCtrl == 'true' && !empty($carouselCtrlContainerClass) && str_contains($carouselCtrlPosition, 'middle'))
				.tns-outer {
					position: relative;
					overflow: hidden;
				}
				
				.{{ $carouselCtrlContainerClass }} {
					position: absolute;
					top: 50%;
					transform: translateY(-50%);
					width: calc(100% - 32px); /* (1rem = 16px) x 2 = 32px */
					display: flex;
					justify-content: space-between;
					pointer-events: none; /* Allow clicks to pass through the container */
					z-index: 10;
				}
				
				.{{ $carouselCtrlContainerClass }} button {
					pointer-events: auto; /* Re-enable clicks on buttons */
					background: rgba(0, 0, 0, 0.25);
					color: white;
					border: none;
				}
				
				.{{ $carouselCtrlContainerClass }} button[data-controls="prev"] {
					margin-left: -8px;
				}
				
				.{{ $carouselCtrlContainerClass }} button[data-controls="next"] {
					margin-right: -8px;
				}
			@endif
			
			{{-- Carousel Navigation Dots --}}
			.tns-nav {
				margin-top: {{ $carouselNavPosition == 'bottom' ? '10px' : '0' }};
				text-align: center;
			}
			.tns-nav [aria-controls] {
				width: 10px;
				height: 10px;
				padding: 0;
				margin: 0 5px;
				border-radius: 50%;
				background: #d6d6d6;
				border: 0;
			}
			.tns-nav [aria-controls].tns-nav-active {
				background: #869791;
			}
			
			/**
			 * =============================================================================
			 * TINY SLIDER SPACING FIX
			 * =============================================================================
			 * Tiny Slider Layout Reset
			 *
			 * Removes unwanted top spacing from tiny-slider elements that can occur due to:
			 * - Default library margins/padding
			 * - Inherited line-height spacing from parent elements
			 * - Browser default styling conflicts
			 *
			 * Dependencies: tiny-slider.js library
			 * @affects .tns-inner, .tns-inner > div
			 * @purpose Ensures carousel slides are flush with container top
			 * @note Uses !important to override library defaults
			 * =============================================================================
			 */
			.tns-inner {
				margin: 0 !important;
				padding: 0 !important;
				vertical-align: top;
				line-height: 0;
			}
			
			/* Reset slide container elements to prevent spacing inheritance */
			.tns-inner > div {
				margin: 0 !important;
				padding: 0 !important;
				vertical-align: top;
			}
		</style>
	@endif
@endsection

@section('after_scripts')
	@parent
	@if ($totalPosts > 0)
		<script>
			onDocumentReady((event) => {
				{{-- Check if RTL or LTR --}}
				const isRTLEnabled = (document.documentElement.getAttribute('dir') === 'rtl');
				
				{{-- Carousel Variables --}}
				{{-- Documentation: https://github.com/ganlanyuan/tiny-slider?tab=readme-ov-file#options --}}
				const totalItems = {{ $totalPosts ?? 0 }};
				const slideBy = {!! is_integer($carouselSlideBy) ? $carouselSlideBy : "'{$carouselSlideBy}'" !!};
				const mouseDrag = {{ $carouselMouseDrag }};
				const controls = {{ $carouselCtrl }};
				const ctrlContainerClass = '{{ $carouselCtrlContainerClass }}';
				const nav = {{ $carouselNav }};
				const navPosition = '{{ $carouselNavPosition }}';
				const loop = {{ $carouselLoop }};
				const rewind = {{ $carouselRewind }};
				const autoplay = {{ $carouselAutoplay }};
				const autoplayTimeout = {{ $carouselAutoplayTimeout }};
				{{-- const autoplayDirection = isRTLEnabled ? 'backward' : 'forward'; --}}
				const autoplayDirection = 'forward';
				const autoplayHoverPause = {{ $carouselAutoplayHoverPause }};
				const trans = {
					'navText': {
						'prev': "{{ t('prev') }}",
						'next': "{{ t('next') }}",
						'start': "{{ t('start') }}",
						'stop': "{{ t('stop') }}",
					}
				};
				
				{{-- Featured Listings Carousel --}}
				const carouselSelector = '.featured-list-slider.{{ $carouselSlug }}';
				const responsive = {
					576: {
						items: 2,
						nav: false
					},
					768: {
						items: 3,
						nav: nav
					},
					992: {
						items: 5,
						nav: nav,
						loop: (totalItems > 5)
					}
				};
				
				{{-- Carousel Options --}}
				const options = {
					container: carouselSelector,
					mode: 'carousel', {{-- 'carousel' or 'gallery' --}}
					axis: 'horizontal', {{-- 'horizontal' or 'vertical' --}}
					items: 5,
					gutter: 10,
					edgePadding: 0,
					autoWidth: true,
					slideBy: slideBy,
					mouseDrag: mouseDrag,
					swipeAngle: false,
					center: false,
					controls: controls,
					nav: nav,
					loop: loop,
					rewind: rewind,
					responsive: responsive,
					autoplay: autoplay
				};
				if (controls) {
					options.controlsText = isRTLEnabled
						? [trans.navText.next, trans.navText.prev]
						: [trans.navText.prev, trans.navText.next];
					options.controlsContainer = `.${ctrlContainerClass}`;
					options.prevButton = '.ctrl-prev';
					options.nextButton = '.ctrl-next';
				}
				if (nav) {
					options.navPosition = navPosition;
				}
				if (autoplay) {
					options.autoplayTimeout = autoplayTimeout;
					options.autoplayDirection = autoplayDirection;
					options.autoplayText = [trans.navText.start, trans.navText.stop];
					options.autoplayHoverPause = autoplayHoverPause;
					options.autoplayButton = false;
					options.autoplayButtonOutput = false;
				}
				
				/* console.log(options); */
				const slider = tns(options);
				
				{{-- Items Title Animation --}}
				{{-- https://animate.style --}}
				const itemsTitles = document.querySelectorAll('.featured-list-slider .card-body > h6');
				if (itemsTitles.length) {
					const animation = 'animate__pulse';
					
					itemsTitles.forEach((element) => {
						element.addEventListener('mouseover', (event) => {
							event.target.classList.add('animate__animated', animation);
						});
						element.addEventListener("mouseout", (event) => {
							event.target.classList.remove('animate__animated', animation);
						});
					})
				}
			});
		</script>
	@endif
@endsection
