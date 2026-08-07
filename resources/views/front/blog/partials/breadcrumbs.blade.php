@php
	$breadcrumbs ??= [];
	$linkClass = linkClass();
@endphp
<div class="container">
	<nav aria-label="breadcrumb" role="navigation" class="blog-breadcrumb">
		<ol class="breadcrumb mb-0 py-3">
			<li class="breadcrumb-item">
				<a href="{{ url('/') }}" class="{{ $linkClass }}">
					<i class="fa-solid fa-house"></i>
				</a>
			</li>
			@foreach($breadcrumbs as $breadcrumb)
				@php
					$bcName = data_get($breadcrumb, 'name');
					$bcUrl = data_get($breadcrumb, 'url');
				@endphp
				@if ($loop->last || empty($bcUrl))
					<li class="breadcrumb-item active" aria-current="page">
						{{ str($bcName)->limit(60) }}
					</li>
				@else
					<li class="breadcrumb-item">
						<a href="{{ $bcUrl }}" class="{{ $linkClass }}">{{ $bcName }}</a>
					</li>
				@endif
			@endforeach
		</ol>
	</nav>
</div>
