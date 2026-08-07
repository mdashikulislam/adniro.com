@extends('front.layouts.master')

@php
	$posts ??= null;
	$featuredPosts ??= collect();
	$category ??= null;
	$title ??= t('blog_page_title');
	$subTitle ??= t('blog_page_sub_title');
	$keyword ??= null;
	$breadcrumbs ??= [];

	$hasHeroImage = !empty(data_get($category, 'image_url'));
@endphp

@section('search')
	@parent
	@if ($hasHeroImage)
		<div class="hero-wrap d-flex align-items-center"
		     style="background: url({{ data_get($category, 'image_url') }}) no-repeat center; background-size: cover;"
		>
			<div class="container text-center">
				@include('helpers.titles.title-4', [
					'title'         => $title,
					'titleClass'    => 'h1 mb-1 fw-bold text-white',
					'subTitle'      => $subTitle,
					'subTitleClass' => 'fs-5 px-3 text-white',
					'lineClass'     => 'border-1 border-white opacity-25',
				])
			</div>
		</div>
	@endif
@endsection

@section('content')
	<div class="main-container" id="blogPage">
		@include('front.blog.partials.breadcrumbs')

		<div class="container mb-4">
			@if (!$hasHeroImage)
				@include('helpers.titles.title-4', [
					'title'    => $title,
					'subTitle' => !empty($subTitle) ? $subTitle : '<i class="bi bi-star-fill"></i>',
				])
			@endif

			{{-- Featured posts --}}
			@if ($featuredPosts->count() > 0)
				<div class="row mb-2">
					<div class="col-12">
						<h2 class="fs-4 fw-bold mb-3">
							<i class="fa-solid fa-star text-warning"></i> {{ t('blog_featured_posts') }}
						</h2>
					</div>
				</div>
				<div class="row">
					@foreach($featuredPosts as $featuredPost)
						@include('front.blog.partials.card', [
							'post'     => $featuredPost,
							'colClass' => 'col-md-6 col-lg-4',
						])
					@endforeach
				</div>
				<hr class="my-4">
			@endif

			<div class="row">
				{{-- Posts list --}}
				<div class="col-12 col-lg-8">
					@if (!empty($posts) && $posts->total() > 0)
						<div class="row">
							@foreach($posts as $blogPostItem)
								@include('front.blog.partials.card', [
									'post'     => $blogPostItem,
									'colClass' => 'col-md-6',
								])
							@endforeach
						</div>

						{{-- Pagination --}}
						<div class="mt-2">
							{!! $posts->links('vendor.pagination.bootstrap-5') !!}
						</div>
					@else
						<div class="alert alert-info text-center mb-0" role="alert">
							{{ t('blog_no_posts_found') }}
						</div>
					@endif
				</div>

				{{-- Sidebar --}}
				<div class="col-12 col-lg-4 mt-4 mt-lg-0">
					@include('front.blog.partials.sidebar')
				</div>
			</div>
		</div>
	</div>

	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
