{{--
	Seller's public page: /user/{username}
	The seller's live listings from every country, with the profile's statistics & the "Follow" button.
--}}
@extends('front.layouts.master')
@php
	$apiResult ??= [];
	$apiExtra ??= [];
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total', 0);
	
	// No canonical tag on empty result pages
	if (empty($posts) || $totalPosts <= 0) {
		view()->share('hideCanonical', true);
	}
	
	$seller = (array)($seller ?? []);
	$sellerStats = (array)($sellerStats ?? []);
	$isOwnProfile ??= false;
	$isFollowing ??= false;
	$sellerCountryName ??= null;
	
	$orderByOptions ??= [];
	$displayModes ??= [];
	$selectedDisplayMode = config('settings.listings_list.display_mode', 'grid-view');
	
	$sellerName = (string)data_get($seller, 'name');
	$sellerUsername = (string)data_get($seller, 'username');
	$isGuestSeller = (data_get($seller, 'type') == 'guest');
	$sellerPhotoUrl = data_get($seller, 'photo_url');
	$sellerCoverUrl = data_get($seller, 'cover_url');
	$sellerAbout = (string)data_get($seller, 'about');
	$sellerSince = data_get($seller, 'since');
	$profileUrl = urlGen()->user(['username' => $sellerUsername]);
	
	// Initials shown when the seller has no photo
	$initials = collect(preg_split('/\s+/u', trim($sellerName)) ?: [])
		->filter()
		->take(2)
		->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
		->implode('');
	$initials = ($initials !== '') ? $initials : mb_strtoupper(mb_substr($sellerUsername, 0, 1));
	
	// 1200 => 1.2K, 3400000 => 3.4M
	$compact = function ($number) {
		$number = (int)$number;
		if ($number >= 1000000) return rtrim(rtrim(number_format($number / 1000000, 1), '0'), '.') . 'M';
		if ($number >= 1000) return rtrim(rtrim(number_format($number / 1000, 1), '0'), '.') . 'K';
		
		return (string)$number;
	};
	
	$stats = [
		['key' => 'listings', 'icon' => 'bi bi-grid-3x3-gap-fill', 'label' => t('profile_listings'), 'value' => (int)data_get($sellerStats, 'listings', 0)],
		['key' => 'followers', 'icon' => 'bi bi-people-fill', 'label' => t('followers'), 'value' => (int)data_get($sellerStats, 'followers', 0)],
		['key' => 'following', 'icon' => 'bi bi-person-check-fill', 'label' => t('following'), 'value' => (int)data_get($sellerStats, 'following', 0)],
		['key' => 'views', 'icon' => 'bi bi-eye-fill', 'label' => t('profile_views'), 'value' => (int)data_get($sellerStats, 'views', 0)],
	];
	$countCountries = (int)data_get($sellerStats, 'countries', 0);
@endphp
@section('body_class', 'page-profile')
@section('content')
	<div class="main-container seller-profile-page">
		<div class="container">
			
			{{-- Profile header --}}
			<section class="profile-hero">
				@if (!empty($sellerCoverUrl))
					<div class="profile-cover profile-cover-image" style="background-image: url('{{ $sellerCoverUrl }}');" aria-hidden="true"></div>
				@else
					<div class="profile-cover" aria-hidden="true">
						<span class="profile-cover-orb profile-cover-orb-1"></span>
						<span class="profile-cover-orb profile-cover-orb-2"></span>
						<span class="profile-cover-orb profile-cover-orb-3"></span>
					</div>
				@endif
				
				<div class="profile-head">
					<div class="profile-avatar{{ empty($sellerPhotoUrl) ? ' profile-avatar-initials' : '' }}">
						@if (!empty($sellerPhotoUrl))
							<img src="{{ $sellerPhotoUrl }}" alt="{{ $sellerName }}">
						@else
							<span>{{ $initials }}</span>
						@endif
					</div>
					
					<div class="profile-identity">
						<h1 class="profile-name">
							<span>{{ $sellerName }}</span>
							@if (data_get($seller, 'is_verified'))
								<i class="bi bi-patch-check-fill profile-verified"
								   data-bs-toggle="tooltip"
								   title="{{ t('verified_seller') }}"
								   aria-label="{{ t('verified_seller') }}"
								></i>
							@endif
						</h1>
						<div class="profile-handle">{{ '@' . $sellerUsername }}</div>
						
						<ul class="profile-meta list-unstyled">
							@if ($isGuestSeller)
								<li class="profile-chip"><i class="bi bi-person"></i> {{ t('guest_seller') }}</li>
							@elseif (data_get($seller, 'is_verified'))
								<li class="profile-chip profile-chip-accent"><i class="bi bi-shield-check"></i> {{ t('verified_seller') }}</li>
							@endif
							@if (!empty($sellerSince))
								<li class="profile-chip">
									<i class="bi bi-calendar3"></i>
									{{ t($isGuestSeller ? 'selling_since' : 'member_since', ['date' => $sellerSince->translatedFormat('M Y')]) }}
								</li>
							@endif
							@if (!empty($sellerCountryName))
								<li class="profile-chip"><i class="bi bi-geo-alt"></i> {{ $sellerCountryName }}</li>
							@endif
							@if ($countCountries > 1)
								<li class="profile-chip"><i class="bi bi-globe2"></i> {{ $countCountries }} {{ mb_strtolower(t('profile_countries')) }}</li>
							@endif
						</ul>
					</div>
					
					<div class="profile-actions">
						@if ($isOwnProfile)
							<a href="{{ url(urlGen()->getAccountBasePath() . '/profile') }}" class="btn profile-btn profile-btn-primary">
								<i class="bi bi-pencil-square"></i> <span>{{ t('edit_profile') }}</span>
							</a>
						@else
							<button type="button"
							        id="profileFollowBtn"
							        class="btn profile-btn profile-btn-primary profile-follow-btn js-follow-btn{{ $isFollowing ? ' is-following' : '' }}"
							        data-count-target="#profileFollowersCount"
							        data-username="{{ $sellerUsername }}"
							        data-url="{{ url(urlGen()->getAccountBasePath() . '/follows/toggle') }}"
							        data-auth="{{ auth()->check() ? '1' : '0' }}"
							        aria-pressed="{{ $isFollowing ? 'true' : 'false' }}"
							>
								<span class="profile-follow-state profile-follow-state-off"><i class="bi bi-person-plus-fill"></i> {{ t('follow') }}</span>
								<span class="profile-follow-state profile-follow-state-on"><i class="bi bi-check2"></i> {{ t('following') }}</span>
								<span class="profile-follow-state profile-follow-state-hover"><i class="bi bi-person-dash"></i> {{ t('unfollow') }}</span>
							</button>
						@endif
						<button type="button"
						        id="profileShareBtn"
						        class="btn profile-btn profile-btn-ghost"
						        data-url="{{ $profileUrl }}"
						        data-title="{{ $sellerName }}"
						        data-copied="{{ t('profile_link_copied') }}"
						        aria-label="{{ t('Share') }}"
						>
							<i class="bi bi-share-fill"></i> <span>{{ t('Share') }}</span>
						</button>
					</div>
				</div>
				
				@if (!empty($sellerAbout))
					<p class="profile-about">{!! nl2br(e($sellerAbout)) !!}</p>
				@elseif ($isOwnProfile)
					<p class="profile-about profile-about-empty">
						<a href="{{ url(urlGen()->getAccountBasePath() . '/profile') }}#about">
							<i class="bi bi-plus-circle"></i> {{ t('add_profile_description') }}
						</a>
					</p>
				@endif
				
				<ul class="profile-stats list-unstyled">
					@foreach($stats as $stat)
						<li class="profile-stat">
							<span class="profile-stat-icon"><i class="{{ $stat['icon'] }}"></i></span>
							<span class="profile-stat-text">
								<strong class="profile-stat-value"
								        @if ($stat['key'] == 'followers') id="profileFollowersCount" data-count="{{ $stat['value'] }}" @endif
								        title="{{ number_format($stat['value']) }}"
								>{{ $compact($stat['value']) }}</strong>
								<span class="profile-stat-label">{{ $stat['label'] }}</span>
							</span>
						</li>
					@endforeach
				</ul>
			</section>
			
			{{-- Listings --}}
			<section class="profile-listings {{ $selectedDisplayMode }} noSideBar">
				<div class="profile-section-head">
					<h2 class="profile-section-title">
						{{ t('listings_of_x', ['name' => $sellerName]) }}
						<span class="profile-section-count">{{ number_format($totalPosts) }}</span>
					</h2>
					
					@if (!empty($posts) && $totalPosts > 0)
						<div class="profile-section-tools">
							{{-- Display Modes --}}
							@if (!empty($displayModes))
								<div class="btn-group results-view-switch">
									@foreach($displayModes as $displayMode => $value)
										@php
											$displayModeUrl = request()->fullUrlWithQuery((array)data_get($value, 'query'));
											$isActive = ($selectedDisplayMode == $displayMode);
										@endphp
										<a href="{!! $displayModeUrl !!}"
										   class="btn btn-outline-primary btn-sm{{ $isActive ? ' active' : '' }}"
										   rel="nofollow"{!! $isActive ? ' aria-current="page"' : '' !!}
										>
											<i class="{{ data_get($value, 'icon') }}"></i>
										</a>
									@endforeach
								</div>
							@endif
							
							{{-- OrderBy --}}
							@if (!empty($orderByOptions))
								<div class="dropdown">
									<a href="#" class="dropdown-toggle text-uppercase {{ linkClass() }} results-sort-btn" data-bs-toggle="dropdown" aria-expanded="false">
										<i class="bi bi-arrow-down-up"></i> {{ t('Sort by') }}
									</a>
									<ul class="dropdown-menu dropdown-menu-end">
										@foreach($orderByOptions as $option)
											@continue(!data_get($option, 'condition'))
											@php
												$currentUrl = request()->fullUrl();
												$currentUrlWithoutOrderBy = urlBuilder($currentUrl)->removeParameter('orderBy')->toString();
												$optionUrl = urlBuilder($currentUrl)->setParameters((array)data_get($option, 'query'))->toString();
												
												$optionParams = collect(urlBuilder($optionUrl)->getAllParameters())->sortKeys()->toArray();
												$currentParams = collect(urlBuilder($currentUrl)->getAllParameters())->sortKeys()->toArray();
												
												$isActive = ($optionParams == $currentParams);
												$optionUrl = ($optionUrl == $currentUrlWithoutOrderBy) ? '#' : $optionUrl;
											@endphp
											<li>
												<a href="{!! $optionUrl !!}" class="dropdown-item{{ $isActive ? ' active' : '' }}" rel="nofollow">
													{{ data_get($option, 'label') }}
												</a>
											</li>
										@endforeach
									</ul>
								</div>
							@endif
						</div>
					@endif
				</div>
				
				@if (!empty($posts) && $totalPosts > 0)
					<div class="results-list">
						@if ($selectedDisplayMode == 'list-view')
							@include('front.search.partials.posts.template.list')
						@elseif ($selectedDisplayMode == 'compact-view')
							@include('front.search.partials.posts.template.compact')
						@else
							@include('front.search.partials.posts.template.grid', ['customCols' => 4])
						@endif
					</div>
					
					{{-- Pagination --}}
					@include('vendor.pagination.api.bootstrap-5')
				@else
					<div class="profile-empty">
						<span class="profile-empty-icon"><i class="bi bi-inbox"></i></span>
						<p class="mb-0">{{ t('no_listings_from_seller') }}</p>
					</div>
				@endif
			</section>
			
		</div>
	</div>
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@section('modal_location')
	@include('front.layouts.partials.modal.location')
@endsection

@section('after_scripts')
	@include('front.user.partials.follow-script')
	<script>
		onDocumentReady((event) => {
			{{-- Share --}}
			const shareBtn = document.getElementById('profileShareBtn');
			if (shareBtn) {
				shareBtn.addEventListener('click', async (e) => {
					e.preventDefault();
					
					const url = shareBtn.dataset.url;
					if (navigator.share) {
						try {
							await navigator.share({title: shareBtn.dataset.title, url: url});
						} catch (error) {
						}
						return;
					}
					
					try {
						await navigator.clipboard.writeText(url);
						jsAlert(shareBtn.dataset.copied, 'success');
					} catch (error) {
						window.prompt('', url);
					}
				});
			}
		});
	</script>
@endsection
