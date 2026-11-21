{{--
	adniro: the phone app bar, fixed at the bottom of the screen below 768px (public/assets/css/mobile-nav.css).
	Home, Search, Sell (raised, in the middle), Saved and Account. Not on the listing form, which has its own
	bottom bar. Guests are sent to sign in for Saved and Account.
--}}
@php
	$mobileNavSignedIn = auth()->check();
	$mobileNavLogin = route('auth.login.showForm');
	[$mobileNavSellUrl, $mobileNavSellAttr] = getCreateListingLinkInfo();
	if (str_starts_with($mobileNavSellUrl, '#')) {
		// Guests who may not post: the sign-in page (the sign-in pop-up is not on every page)
		[$mobileNavSellUrl, $mobileNavSellAttr] = [$mobileNavLogin, ''];
	}

	$mobileNavItems = [
		[
			'url'    => url('/'),
			'icon'   => 'house-door',
			'filled' => 'house-door-fill',
			'label'  => trans('mobile_nav.home'),
			'active' => request()->routeIs('homepage'),
		],
		[
			'url'    => urlGen()->searchWithoutQuery(),
			'icon'   => 'search',
			'filled' => 'search',
			'label'  => trans('mobile_nav.search'),
			'active' => request()->routeIs('browse.listings*', 'search*'),
		],
		'sell',
		[
			'url'    => $mobileNavSignedIn ? route('account.savedListings') : $mobileNavLogin,
			'icon'   => 'heart',
			'filled' => 'heart-fill',
			'label'  => trans('mobile_nav.saved'),
			'active' => request()->routeIs('account.savedListings*'),
		],
		[
			'url'    => $mobileNavSignedIn ? route('account.overview') : $mobileNavLogin,
			'icon'   => 'person',
			'filled' => 'person-fill',
			'label'  => trans('mobile_nav.account'),
			'active' => request()->routeIs('account.*', 'auth.*') && !request()->routeIs('account.savedListings*'),
		],
	];
@endphp
<nav class="adn-appbar d-md-none" aria-label="{{ trans('mobile_nav.label') }}" data-adn-appbar>
	<ul class="adn-appbar-list">
		@foreach ($mobileNavItems as $item)
			@if ($item === 'sell')
				<li class="adn-appbar-item adn-appbar-item-sell">
					<a href="{{ $mobileNavSellUrl }}" class="adn-appbar-link adn-appbar-sell"{!! $mobileNavSellAttr !!}>
						<span class="adn-appbar-sell-tile" aria-hidden="true"><i class="bi bi-plus-lg"></i></span>
						<span class="adn-appbar-label">{{ trans('mobile_nav.sell') }}</span>
					</a>
				</li>
				@continue
			@endif
			<li class="adn-appbar-item">
				<a href="{{ $item['url'] }}" @class(['adn-appbar-link', 'is-active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
					<span class="adn-appbar-icon" aria-hidden="true">
						<i class="bi bi-{{ $item['active'] ? $item['filled'] : $item['icon'] }}"></i>
					</span>
					<span class="adn-appbar-label">{{ $item['label'] }}</span>
				</a>
			</li>
		@endforeach
	</ul>
</nav>
