@php
	$page ??= [];
	$imageUrl = data_get($page, 'image_url');
@endphp
@if (!empty($imageUrl))
	<div class="hero-wrap d-flex align-items-center" style="background:url({{ $imageUrl }}) no-repeat center;background-size:cover;">
		<div class="container text-center">
			@include('helpers.titles.title-4', [
				'title'         => data_get($page, 'name'),
				'titleClass'    => 'h1 mb-1 fw-bold text-white',
				'titleStyle'    => 'color: ' . data_get($page, 'name_color'),
				'subTitle'      => data_get($page, 'title'),
				'subTitleClass' => 'fs-5 px-3 text-white',
				'subTitleStyle' => 'color: ' . data_get($page, 'title_color'),
				'lineClass'     => 'border-1 border-white opacity-25',
			])
		</div>
	</div>
@endif
