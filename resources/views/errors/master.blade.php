@php
	$htmlLang = getLangTag(config('app.locale'));
	$htmlDir = (config('lang.direction') == 'rtl') ? ' dir="rtl"' : '';
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}"{!! $htmlDir !!}>
<head>
	<meta charset="{{ config('larapen.core.charset', 'utf-8') }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<meta name="googlebot" content="noindex">
	<title>@yield('title')</title>
	
	@if (file_exists(public_path('manifest.json')))
		<link rel="manifest" href="{{ url()->asset('manifest.json') }}">
	@endif
	
	{{-- App CSS files (Handled by Mix) --}}
	@if (config('lang.direction') == 'rtl')
		<link href="https://fonts.googleapis.com/css?family=Cairo|Changa" rel="stylesheet">
		<link href="{{ url(mix('dist/front/styles.rtl.css')) }}" rel="stylesheet">
	@else
		<link href="{{ url(mix('dist/front/styles.css')) }}" rel="stylesheet">
	@endif
</head>
<body class="bg-body text-body-emphasis d-flex align-items-center min-vh-100">

<div class="container text-center">
	<div class="row justify-content-center">
		<div class="col-md-8 col-lg-6">
			
			<div class="vstack gap-3">
				<div class="text-danger display-1 fw-bold border bg-body-tertiary rounded p-5">
					@yield('status')
				</div>
				<h1 class="mb-0 fs-3 fw-bold text-capitalize">
					@yield('title')
				</h1>
				<p class="text-secondary">
					@yield('message')
				</p>
				
				<div class="d-flex justify-content-center">
					@if (isFromInstallOrUpgradeProcess())
						@php
							if (in_array(request()->method(), ['POST', 'PUT'])) {
								$linkLabel = t('go_back');
								$linkIcon = 'bi bi-arrow-left';
								$linkUrl = url()->previous();
							} else {
								$linkLabel = 'Reload'; // Reload | Refresh
								$linkIcon = 'bi bi-arrow-clockwise';
								$linkUrl = url()->full();
							}
						@endphp
						<a href="{{ $linkUrl }}" class="btn btn-primary">
							<i class="{{ $linkIcon }}"></i> {{ $linkLabel }}
						</a>
					@else
						<a href="/" class="btn btn-primary me-1">
							<i class="bi bi-house-door me-1"></i>{{ t('go_home') }}
						</a>
						<a href="{{ url()->previous() }}" class="btn btn-secondary ms-1">
							<i class="bi bi-arrow-left me-1"></i>{{ t('go_back') }}
						</a>
					@endif
				</div>
			</div>
		
		</div>
	</div>
</div>

{{-- App JS files (Handled by Mix) --}}
<script src="{{ url(mix('dist/front/scripts.js')) }}"></script>
</body>
</html>
