@php
	$headerMenu ??= [];
	$headerMenuData = (array)data_get($headerMenu, 'data');
	$totalHeaderMenus = (int)data_get($headerMenu, 'meta.total', 0);
	
	$savedType = null;
	$openOnHover = ''; // ' open-on-hover'
	
	// adniro: how the two entries the burger menu leaves out are told apart (see below)
	$accountEntryRoutes = ['auth.login.showForm', 'auth.logout', 'impersonate.leave'];
	$createListingRoutes = ['listing.create.ms.showForm', 'listing.create.ss.showForm'];
@endphp
@foreach($headerMenuData as $menu)
	@php
		$labelHtml = data_get($menu, 'label_html');
		$shouldDisplay = data_get($menu, 'should_display');
		$children = data_get($menu, 'children');
		
		$canBeDisplayed = ($shouldDisplay && !empty($labelHtml));
		
		$type = data_get($menu, 'type');
		$ms = ($savedType == 'button' && $type == 'button') ? ' ms-1' : '';
		
		// adniro: on small screens two entries are within reach already, so the burger menu leaves them out:
		// the account entry (the header's account button has its links below 1200px, mobile-account-menu.blade.php)
		// and "Create Listing" (the app bar's "Post ad" below 768px, mobile-nav.blade.php)
		$isAccountEntry = collect($children)->contains(
			fn ($child) => in_array(data_get($child, 'route_name'), $accountEntryRoutes, true)
		);
		$isCreateListing = in_array(data_get($menu, 'route_name'), $createListingRoutes, true);
		$fromScreen = $isAccountEntry ? ' d-none d-xl-block' : ($isCreateListing ? ' d-none d-md-block' : '');
	@endphp
	@continue(!$canBeDisplayed)
	@if (!empty($children))
		<li class="nav-item{{ $ms }}{{ $fromScreen }} dropdown{{ $openOnHover }}">
			{!! $labelHtml !!}
			{{-- adniro: lined up with the button's right edge: these menus sit at the end of the header, and a menu
			     opening to the right ran off the screen --}}
			<ul class="dropdown-menu dropdown-menu-end shadow-sm">
				@foreach($children as $subMenu)
					@php
						$subLabelHtml = data_get($subMenu, 'label_html');
						$subChildren = data_get($subMenu, 'children');
						$subShouldDisplay = data_get($subMenu, 'should_display');
						$subCanBeDisplayed = ($subShouldDisplay && !empty($subLabelHtml));
					@endphp
					@continue(!$subCanBeDisplayed)
					<li>
						{!! $subLabelHtml !!}
					</li>
				@endforeach
			</ul>
		</li>
	@else
		<li class="nav-item{{ $ms }}{{ $fromScreen }}">
			{!! $labelHtml !!}
		</li>
	@endif
	@php
		$savedType = $type;
	@endphp
@endforeach
