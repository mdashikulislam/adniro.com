@php
	use App\Helpers\Common\Num;
	use Illuminate\Support\Collection;

	$accountMenu ??= collect();
	$accountMenu = ($accountMenu instanceof Collection) ? $accountMenu : collect();

	$authUser ??= auth()->user();
	$sidebarUserName = $authUser->name ?? '--';
	$sidebarUserPhotoUrl = $authUser->photo_url ?? config('larapen.media.avatar');
	$sidebarUserHandle = !empty($authUser->username ?? null) ? '@' . $authUser->username : ($authUser->email ?? '');
	$sidebarProfileUrl = !empty($authUser) ? urlGen()->user($authUser) : null;

	$groupIcons = [
		'listings' => 'bi bi-collection-fill',
		'account'  => 'bi bi-person-fill-gear',
		'admin'    => 'bi bi-speedometer2',
	];
@endphp
@section('body_class', 'page-account')
<aside class="account-sidebar">
	{{-- User card --}}
	<div class="account-user-card">
		<span class="account-user-glow" aria-hidden="true"></span>
		<div class="account-user-avatar">
			<img src="{{ $sidebarUserPhotoUrl }}" alt="{{ $sidebarUserName }}">
		</div>
		<div class="account-user-info">
			<span class="account-user-name">{{ $sidebarUserName }}</span>
			@if (!empty($sidebarUserHandle))
				<span class="account-user-handle">{{ $sidebarUserHandle }}</span>
			@endif
		</div>
		@if (!empty($sidebarProfileUrl))
			<a href="{{ $sidebarProfileUrl }}" class="account-user-link" title="{{ t('Public profile') }}">
				<i class="bi bi-box-arrow-up-right"></i>
			</a>
		@endif
	</div>

	{{-- Menu --}}
	<div class="account-nav-card">
		@if ($accountMenu->isNotEmpty())
			@foreach($accountMenu as $group => $menu)
				@php
					$collapseId = 'accnav-' . str($group)->slug();
					$groupKey = str($group)->lower()->toString();
					$groupIcon = match (true) {
						str_contains($groupKey, 'listing') => $groupIcons['listings'],
						str_contains($groupKey, 'admin')   => $groupIcons['admin'],
						default                            => $groupIcons['account'],
					};
				@endphp
				<div class="account-nav-group">
					<button type="button"
					        class="account-nav-heading"
					        data-bs-toggle="collapse"
					        data-bs-target="#{{ $collapseId }}"
					        aria-expanded="true"
					        aria-controls="{{ $collapseId }}"
					>
						<span class="account-nav-heading-icon"><i class="{{ $groupIcon }}"></i></span>
						<span class="account-nav-heading-text">{{ $group }}</span>
						<i class="bi bi-chevron-down account-nav-heading-caret"></i>
					</button>
					@if (!empty($menu))
						<div class="collapse show" id="{{ $collapseId }}">
							<ul class="account-nav list-unstyled">
								@foreach($menu as $key => $item)
									@php
										$isActive = !empty($item['isActive']);
										$isLogout = str_contains((string)($item['url'] ?? ''), 'logout');
										$isDanger = $isLogout || str_contains((string)($item['url'] ?? ''), 'closing');
									@endphp
									<li class="account-nav-item{{ $isActive ? ' is-active' : '' }}{{ $isDanger ? ' is-danger' : '' }}">
										<a href="{{ $item['url'] }}"{!! $isActive ? ' aria-current="page"' : '' !!}>
											<span class="account-nav-icon"><i class="{{ $item['icon'] }}"></i></span>
											<span class="account-nav-label">{{ $item['name'] }}</span>
											@if (!empty($item['countVar']))
												<span class="account-nav-count{{ $item['cssClass'] ?? '' }}">
													{{ Num::short($item['countVar']) }}
												</span>
											@endif
										</a>
									</li>
								@endforeach
							</ul>
						</div>
					@endif
				</div>
			@endforeach
		@endif
	</div>
</aside>
