{{--
	adniro: the phone header's account button (below 1200px) opens a menu: for members, the account links of
	the user menu (FrontController::getUserMenu(), the ones meant for a dropdown), log out last; for guests,
	log in and create an account. Styles: public/assets/css/mobile-nav.css.
--}}
@php
	$authUser ??= auth()->user();
	$userMenu ??= collect();
	$accountMenuItems = collect($userMenu)->filter(fn ($item) => !empty($item['inDropdown']))->values();
	$logOutUrl = urlGen()->signOut();
	$accountLinks = $accountMenuItems->reject(fn ($item) => ($item['url'] ?? null) === $logOutUrl);
	$hasLogOut = $accountMenuItems->contains(fn ($item) => ($item['url'] ?? null) === $logOutUrl);
@endphp
<div class="dropdown adn-mhead-account ms-auto d-xl-none">
	<button type="button" class="adn-mhead-btn" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"
	        aria-label="{{ $authUser ? trans('mobile_nav.account') : trans('mobile_nav.sign_in') }}">
		<i class="bi bi-person{{ $authUser ? '-check' : '' }}" aria-hidden="true"></i>
	</button>
	<div class="dropdown-menu dropdown-menu-end adn-account-menu">
		@if ($authUser)
			<div class="adn-account-menu-head">
				<span class="adn-account-menu-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string)$authUser->name, 0, 1)) }}</span>
				<span class="adn-account-menu-who">
					<span class="adn-account-menu-name">{{ $authUser->name }}</span>
					@if (!empty($authUser->email))
						<span class="adn-account-menu-email">{{ $authUser->email }}</span>
					@endif
				</span>
			</div>
			@foreach ($accountLinks as $item)
				<a href="{{ $item['url'] }}" @class(['dropdown-item', 'adn-account-menu-item', 'active' => !empty($item['isActive'])])>
					<span class="adn-account-menu-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span>
					<span>{{ $item['name'] }}</span>
				</a>
			@endforeach
			@include('front.layouts.partials.app-install-menu-item')
			@if ($hasLogOut)
				<div class="dropdown-divider"></div>
				<a href="{{ $logOutUrl }}" class="dropdown-item adn-account-menu-item adn-account-menu-logout">
					<span class="adn-account-menu-icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
					<span>{{ trans('auth.log_out') }}</span>
				</a>
			@endif
		@else
			<a href="{{ route('auth.login.showForm') }}" class="dropdown-item adn-account-menu-item">
				<span class="adn-account-menu-icon" aria-hidden="true"><i class="bi bi-box-arrow-in-right"></i></span>
				<span>{{ trans('auth.log_in') }}</span>
			</a>
			<a href="{{ route('auth.register.showForm') }}" class="dropdown-item adn-account-menu-item">
				<span class="adn-account-menu-icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
				<span>{{ trans('auth.create_account') }}</span>
			</a>
			@include('front.layouts.partials.app-install-menu-item')
		@endif
	</div>
</div>
