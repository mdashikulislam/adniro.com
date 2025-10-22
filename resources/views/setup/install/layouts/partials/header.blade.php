@php
	use App\Helpers\Common\Files\Storage\StorageDisk;
	
	// Logo
	$logoFactoryUrl = config('larapen.media.logo-factory');
	$logoUrl = '';
	try {
		if (is_link(public_path('storage'))) {
			$disk = StorageDisk::getDisk();
			$defaultLogo = config('larapen.media.logo');
			if (!empty($defaultLogo) && $disk->exists($defaultLogo)) {
				$logoUrl = $disk->url($defaultLogo);
			}
		}
	} catch (\Throwable $e) {}
	$logoUrl = empty($logoUrl) ? $logoFactoryUrl : $logoUrl;
	$logoStyle = 'max-width:200px; max-height:40px; width:auto; height:auto;';
@endphp
<header>
	<nav class="navbar navbar-expand-md bg-body-tertiary border-bottom" role="navigation">
		<div class="container-fluid">
			
			{{-- Logo --}}
			<a href="{{ url('/') }}" class="navbar-brand logo logo-title">
				<img src="{{ $logoUrl }}" alt="logo" style="{!! $logoStyle !!}">
			</a>
			
			{{-- Toggle Nav (Mobile) --}}
			<button class="navbar-toggler float-end"
			        type="button"
			        data-bs-toggle="collapse"
			        data-bs-target="#navbarNav"
			        aria-controls="navbarNav"
			        aria-expanded="false"
			        aria-label="Toggle navigation"
			>
				<span class="navbar-toggler-icon"></span>
			</button>
			
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav me-md-auto"></ul>
				<ul class="navbar-nav ms-auto"></ul>
			</div>
			
		</div>
	</nav>
</header>
