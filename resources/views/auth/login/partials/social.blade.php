@php
	use App\Services\Auth\App\Helpers\SocialLogin\SocialLoginButton;
	
	$socialLoginProviders = socialLogin()->providersForConnection(strict: true);
	$labelType = getSocialLoginButtonType();
	
	$defaultPosition = 'top';
	$position ??= $defaultPosition;
	$position = in_array($position, ['top', 'topWithTitle', 'bottom']) ? $position : $defaultPosition;
	
	$defaultPage = 'login';
	$page ??= $defaultPage;
	$page = in_array($page, ['login', 'register', 'modal']) ? $page : $defaultPage;
	
	$topSeparator = ($page == 'register')
		? trans('auth.register_with_title')
		: trans('auth.login_with_title');
	
	$bottomSeparator = ($page == 'register')
		? trans('auth.or_register_with')
		: trans('auth.or_login_with');
	$bottomSeparator = ($labelType == SocialLoginButton::LoginWithDefault->value)
		? trans('auth.or')
		: $bottomSeparator;
	
	$boxedCol = (!empty($boxedCol) && is_numeric($boxedCol)) ? $boxedCol : 12;
@endphp
@if (!empty($socialLoginProviders))
	@php
		$sGutter = 'gx-2 gy-2';
		if (isset($socialCol) && !empty($socialCol) && is_numeric($socialCol)) {
			if ($socialCol >= 10) {
				$sGutter = 'gx-2 gy-1';
			}
			$sCol = 'col-xl-6 col-lg-6 col-md-6';
			$sCol = str_replace('-6', '-' . $socialCol, $sCol);
		} else {
			$sCol = 'col-xl-6 col-lg-6 col-md-6';
		}
	@endphp
	
	@if ($position == 'bottom')
		<div class="d-flex align-items-center my-4">
			<hr class="flex-grow-1">
			<span class="mx-2 text-2 text-muted fw-300">{{ $bottomSeparator }}</span>
			<hr class="flex-grow-1">
		</div>
	@endif
	@if ($position == 'topWithTitle')
		<div class="d-flex align-items-center my-4">
			<hr class="flex-grow-1">
			<span class="mx-2 text-2 text-muted fw-300">{{ $topSeparator }}</span>
			<hr class="flex-grow-1">
		</div>
	@endif
	@if ($page != 'modal' && !in_array($position, ['bottom', 'topWithTitle']))
		<div class="d-flex align-items-center my-3"></div>
	@endif
	
	<div class="row mb-3 social-media d-flex justify-content-center {{ $sGutter }}">
		@foreach($socialLoginProviders as $provider => $providerData)
			@php
				$btnClass = data_get($providerData, 'btnClass');
				$iconClass = data_get($providerData, 'iconClass');
				$url = data_get($providerData, 'url');
				$label = data_get($providerData, 'label');
				$title = strip_tags($label);
			@endphp
			<div class="{{ $sCol }} col-sm-12 col-12 d-grid">
				<div class="col-xl-12 col-md-12 col-sm-12 col-12 btn {{ $btnClass }}">
					<a href="{{ $url }}" title="{!! $title !!}">
						<i class="{{ $iconClass }}"></i> {!! $label !!}
					</a>
				</div>
			</div>
		@endforeach
	</div>
	
	@if ($position == 'topWithTitle')
		<div class="d-flex align-items-center my-4">
			<hr class="flex-grow-1">
			<span class="mx-2 text-2 text-muted fw-300">{{ trans('auth.or') }}</span>
			<hr class="flex-grow-1">
		</div>
	@endif
	@if ($position == 'top')
		<div class="d-flex align-items-center my-4">
			<hr class="flex-grow-1">
			<span class="mx-2 text-2 text-muted fw-300">{{ trans('auth.or') }}</span>
			<hr class="flex-grow-1">
		</div>
	@endif
@endif
