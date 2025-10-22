@php
	$footerLinksAreEnabled = (config('settings.footer.hide_links') != '1');
	
	$socialLinksAreEnabled = (
		config('settings.social_link.facebook_page_url')
		|| config('settings.social_link.twitter_url')
		|| config('settings.social_link.tiktok_url')
		|| config('settings.social_link.linkedin_url')
		|| config('settings.social_link.pinterest_url')
		|| config('settings.social_link.instagram_url')
		|| config('settings.social_link.youtube_url')
		|| config('settings.social_link.vimeo_url')
		|| config('settings.social_link.vk_url')
		|| config('settings.social_link.tumblr_url')
		|| config('settings.social_link.flickr_url')
	);
	$appsLinksAreEnabled = (
		config('settings.footer.ios_app_url')
		|| config('settings.footer.android_app_url')
	);
	$socialAndAppsLinksAreEnabled = ($socialLinksAreEnabled || $appsLinksAreEnabled);
	
	$paymentLogosAreEnabled = (config('settings.footer.hide_payment_plugins_logos') != '1');
	
	// Footer's theme CSS Class
	$isFooterDarkThemeEnabled = (config('settings.footer.dark') == '1');
	$footerColor = $isFooterDarkThemeEnabled ? ' bg-black border-light-subtle text-light text-opacity-75' : ' bg-body-tertiary';
	$borderColor = $isFooterDarkThemeEnabled ? ' border-dark border-opacity-75' : ' border-light-subtle border-opacity-75';
	$linkClass = $isFooterDarkThemeEnabled ? linkClass('light') . ' link-opacity-75' : linkClass('body-emphasis');
	$imgBgColor = ' bg-light-subtle';
	
	// Footer's spacing
	$isFooterHighSpacingEnabled = (config('settings.footer.high_spacing') == '1');
	$bsSize = $isFooterHighSpacingEnabled ? '5' : '4';
	$subContainerPt = $isFooterHighSpacingEnabled ? ' pt-3' : '';
	
	// Footer's full width
	$isFullWidthFooter = (config('settings.footer.full_width') == '1');
	$containerClass = $isFullWidthFooter ? 'container-fluid' : 'container';
	$containerPxClass = $isFullWidthFooter ? " px-lg-{$bsSize} px-0 py-0" : ' p-0';
@endphp
<footer>
	@php
		$rowColsLg = $socialAndAppsLinksAreEnabled ? 'row-cols-lg-4' : 'row-cols-lg-3';
		$rowColsMd = 'row-cols-md-3';
		
		$borderTopCopy = " border-top{$borderColor} pt-{$bsSize}";
		$mbCopy = " mb-{$bsSize}";
		if (!$footerLinksAreEnabled) {
			$borderTopCopy = '';
			$mbCopy = ' mb-5';
		}
	@endphp
	<div class="container-fluid border-top{{ $footerColor }} pt-5 pb-0 mt-4">
		<div class="{{ $containerClass . $containerPxClass }} my-0{{ $subContainerPt }}">
			
			<div class="row {{ $rowColsLg }} {{ $rowColsMd }} row-cols-sm-2 row-cols-2 g-3">
				@if ($footerLinksAreEnabled)
					@include('front.layouts.partials.navs.menus.footer')
					
					@if ($socialAndAppsLinksAreEnabled)
						<div class="col">
							<div class="row">
								@php
									$mbAppsLinks = $socialLinksAreEnabled ? ' mb-3' : '';
									$styleAppsLinks = 'style="max-width: 135px; max-height: 40px; width: auto; height: auto;"';
									$mbSocialLinksTitle = 'mb-4';
								@endphp
								@if ($appsLinksAreEnabled)
									<div class="col-sm-12 col-12 p-lg-0{{ $mbAppsLinks }}">
										<h4 class="fs-6 fw-bold text-uppercase mb-4">
											{{ t('Mobile Apps') }}
										</h4>
										<div class="row">
											@if (config('settings.footer.ios_app_url'))
												<div class="col-12 col-sm-6">
													<a class="" target="_blank" href="{{ config('settings.footer.ios_app_url') }}">
														<span class="visually-hidden">{{ t('iOS app') }}</span>
														<img
																src="{{ url('images/site/app-store-badge.svg') }}"
																alt="{{ t('Available on the App Store') }}" {!! $styleAppsLinks !!}
														>
													</a>
												</div>
											@endif
											@if (config('settings.footer.android_app_url'))
												<div class="col-12 col-sm-6">
													<a class="app-icon" target="_blank" href="{{ config('settings.footer.android_app_url') }}">
														<span class="visually-hidden">{{ t('Android App') }}</span>
														<img
																src="{{ url('images/site/google-play-badge.svg') }}"
																alt="{{ t('Available on Google Play') }}" {!! $styleAppsLinks !!}
														>
													</a>
												</div>
											@endif
										</div>
									</div>
									@php
										$mbSocialLinksTitle = 'mt-0 mb-2';
									@endphp
								@endif
								
								@if ($socialLinksAreEnabled)
									<div class="col-sm-12 col-12 p-lg-0">
										<h4 class="fs-6 fw-bold text-uppercase {!! $mbSocialLinksTitle !!}">
											{{ t('Follow us on') }}
										</h4>
										<ul class="mb-0 list-unstyled list-inline mx-0 social-media social-links">
											@if (config('settings.social_link.facebook_page_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="facebook"
													   href="{{ config('settings.social_link.facebook_page_url') }}"
													   title="Facebook"
													>
														<i class="fa-brands fa-square-facebook"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.twitter_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="x-twitter"
													   href="{{ config('settings.social_link.twitter_url') }}"
													   title="X (Twitter)"
													>
														<i class="fa-brands fa-square-x-twitter"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.instagram_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="instagram"
													   href="{{ config('settings.social_link.instagram_url') }}"
													   title="Instagram"
													>
														<i class="fa-brands fa-square-instagram"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.linkedin_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="linkedin"
													   href="{{ config('settings.social_link.linkedin_url') }}"
													   title="LinkedIn"
													>
														<i class="fa-brands fa-linkedin"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.pinterest_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="pinterest"
													   href="{{ config('settings.social_link.pinterest_url') }}"
													   title="Pinterest"
													>
														<i class="fa-brands fa-square-pinterest"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.tiktok_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="tiktok"
													   href="{{ config('settings.social_link.tiktok_url') }}"
													   title="Tiktok"
													>
														<i class="fa-brands fa-tiktok"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.youtube_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="youtube"
													   href="{{ config('settings.social_link.youtube_url') }}"
													   title="YouTube"
													>
														<i class="fa-brands fa-youtube"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.vimeo_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="vimeo"
													   href="{{ config('settings.social_link.vimeo_url') }}"
													   title="Vimeo"
													>
														<i class="fa-brands fa-vimeo"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.vk_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="vk"
													   href="{{ config('settings.social_link.vk_url') }}"
													   title="VK (VKontakte)"
													>
														<i class="fa-brands fa-vk"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.tumblr_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="tumblr"
													   href="{{ config('settings.social_link.tumblr_url') }}"
													   title="Tumblr"
													>
														<i class="fa-brands fa-square-tumblr"></i>
													</a>
												</li>
											@endif
											@if (config('settings.social_link.flickr_url'))
												<li class="list-inline-item me-0 px-0">
													<a class="flickr"
													   href="{{ config('settings.social_link.flickr_url') }}"
													   title="Flickr"
													>
														<i class="fa-brands fa-flickr"></i>
													</a>
												</li>
											@endif
										</ul>
									</div>
								@endif
							</div>
						</div>
					@endif
				@endif
			</div>
			
			@php
				$mtCopy = " mt-md-{$bsSize} mt-{$bsSize} pt-2";
			@endphp
			@if ($paymentLogosAreEnabled && isset($paymentMethods) && $paymentMethods->count() > 0)
				@php
					$mtPay = " mt-{$bsSize}";
					$borderTopPay = " border-top{$borderColor} pt-md-{$bsSize} pt-3";
					if (!$footerLinksAreEnabled) {
						$mtPay = ' mt-0';
						$borderTopPay = '';
					}
					$borderTopCopy = " border-top{$borderColor} pt-{$bsSize}";
				@endphp
			@else
				@php
					$mtCopy = ' mt-0';
				@endphp
				@if ($footerLinksAreEnabled)
					@php
						$mtCopy = " mt-md-{$bsSize} mt-{$bsSize} pt-2";
					@endphp
				@endif
			@endif
			
			{{-- Payment Plugins --}}
			@if ($paymentLogosAreEnabled && isset($paymentMethods) && $paymentMethods->count() > 0)
				<div class="row">
					<div class="col-12 text-center{{ $borderTopPay . $mtPay }}">
						@foreach($paymentMethods as $paymentMethod)
							@if (file_exists(plugin_path($paymentMethod->name, 'public/images/payment.png')))
								<img src="{{ url('plugins/' . $paymentMethod->name . '/images/payment.png') }}"
								     alt="{{ $paymentMethod->display_name }}"
								     title="{{ $paymentMethod->display_name }}"
								     class="img-thumbnail m-1{{ $imgBgColor }}"
								     style="width: auto; height: 44px;"
								>
							@endif
						@endforeach
					</div>
				</div>
			@endif
			
			{{-- Copyright Info --}}
			<div class="row">
				<div class="col-12">
					<div class="w-100 d-flex justify-content-between small{{ $borderTopCopy . $mbCopy . $mtCopy }}">
						@php
							$siteName = config('settings.app.name');
							$hidePoweredBy = (config('settings.footer.hide_powered_by') == '1');
							$poweredByInfo = config('settings.footer.powered_by_text');
							$copyrightAlignClass = $hidePoweredBy ? 'w-100 text-center' : 'text-start';
							$itemName = config('larapen.core.item.name', 'AppName');
							$itemUrl = config('larapen.core.item.url', '#');
						@endphp
						<div class="{{ $copyrightAlignClass }}">
							&copy; {{ date('Y') }} {!! $siteName !!}. {{ t('all_rights_reserved') }}.
						</div>
						
						@if (!$hidePoweredBy)
							<div class="text-end">
								@if (!empty($poweredByInfo))
									{{ t('Powered by') }} {!! $poweredByInfo !!}
								@else
									{{ t('Powered by') }} <a href="{{ $itemUrl }}" title="{{ $itemName }}" class="{{ $linkClass }}">{{ $itemName }}</a>
								@endif
							</div>
						@endif
					</div>
				</div>
			</div>
			
		</div>
	</div>
</footer>
