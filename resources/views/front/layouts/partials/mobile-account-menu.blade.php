{{--
	adniro: the phone header's account button (below 1200px) opens a menu: for members, the account links of
	the user menu (FrontController::getUserMenu(), the ones meant for a dropdown), log out last; for guests,
	log in and create an account. A member who uploaded a photo gets it, in a circle, in place of the icon and
	of the initial at the top of the menu. Styles: public/assets/css/mobile-nav.css.
--}}
@php
	$authUser ??= auth()->user();
	$userMenu ??= collect();
	$accountMenuItems = collect($userMenu)->filter(fn ($item) => !empty($item['inDropdown']))->values();
	$logOutUrl = urlGen()->signOut();
	$accountLinks = $accountMenuItems->reject(fn ($item) => ($item['url'] ?? null) === $logOutUrl);
	$hasLogOut = $accountMenuItems->contains(fn ($item) => ($item['url'] ?? null) === $logOutUrl);
	// The member's own photo only: without one, photo_url is the site's default avatar
	$accountPhotoUrl = ($authUser && !empty($authUser->photo_path)) ? (string)$authUser->photo_url : null;
@endphp
<div class="dropdown adn-mhead-account ms-auto d-xl-none">
	<button type="button" @class(['adn-mhead-btn', 'has-photo' => $accountPhotoUrl]) data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"
	        aria-label="{{ $authUser ? trans('mobile_nav.account') : trans('mobile_nav.sign_in') }}">
		@if ($accountPhotoUrl)
			<img src="{{ $accountPhotoUrl }}" alt="" class="adn-mhead-photo" width="40" height="40" decoding="async">
		@else
			<i class="bi bi-person{{ $authUser ? '-check' : '' }}" aria-hidden="true"></i>
		@endif
	</button>
	<div class="dropdown-menu dropdown-menu-end adn-account-menu">
		@if ($authUser)
			<div class="adn-account-menu-head">
				@if ($accountPhotoUrl)
					<span class="adn-account-menu-avatar has-photo" aria-hidden="true">
						<img src="{{ $accountPhotoUrl }}" alt="" width="40" height="40" decoding="async">
					</span>
				@else
					<span class="adn-account-menu-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string)$authUser->name, 0, 1)) }}</span>
				@endif
				<span class="adn-account-menu-who">
					<span class="adn-account-menu-name">{{ $authUser->name }}</span>
					@if (!empty($authUser->email))
						<span class="adn-account-menu-email">{{ $authUser->email }}</span>
					@endif
				</span>
			</div>
			{{-- adniro: the member's own public profile, in a new tab --}}
			<a href="{{ route('account.public-profile') }}" target="_blank" rel="noopener" class="dropdown-item adn-account-menu-item">
				<span class="adn-account-menu-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
				<span>{{ trans('mobile_nav.public_profile') }} <i class="bi bi-arrow-up-right small" aria-hidden="true"></i></span>
			</a>
			@foreach ($accountLinks as $item)
				<a href="{{ $item['url'] }}" @class(['dropdown-item', 'adn-account-menu-item', 'active' => !empty($item['isActive'])])>
					<span class="adn-account-menu-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span>
					<span>{{ $item['name'] }}</span>
				</a>
			@endforeach
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
		@endif
	</div>
</div>
