@php
	// Footer's theme CSS Class
	$isFooterDarkThemeEnabled = (config('settings.footer.dark') == '1');
	$footerColor = $isFooterDarkThemeEnabled ? ' bg-black border-light-subtle text-light text-opacity-75' : ' bg-body-tertiary';
	$linkClass = $isFooterDarkThemeEnabled ? linkClass('light') . ' link-opacity-75' : linkClass('body-emphasis');
@endphp
<footer>
	<div class="container-fluid border-top{{ $footerColor }} pt-5 pb-5 mt-4">
		<div class="container p-0 my-0">
			
			<div class="row">
				<div class="col-12">
					<div class="w-100 d-flex justify-content-between small">
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
