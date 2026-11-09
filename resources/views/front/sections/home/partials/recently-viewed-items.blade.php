{{-- Cards of the "Recently viewed" row (rendered for the AJAX call of the homepage) --}}
@php
	$posts ??= [];
	$totalPosts ??= 0;
	$city = null;
	$cat = null;
@endphp
@if (!empty($posts) && $totalPosts > 0)
	@include('front.search.partials.posts.template.grid', ['customCols' => 4])
@endif
