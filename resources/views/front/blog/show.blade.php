@extends('front.layouts.master')

@php
	/**
	 * @var \App\Models\BlogPost $entry
	 * Note: The entry is NOT named '$post', since the variables defined in this view are shared
	 * with the master layout, that applies listings specific logic when a '$post' variable exists.
	 */
	$entry = $blogPost ?? null;
	$relatedPosts ??= collect();
	$breadcrumbs ??= [];

	$category = data_get($entry, 'category');
	$author = data_get($entry, 'author');
	// Fallbacks to the app's default picture ("no image" placeholder) when the post hasn't any picture
	$imageUrl = data_get($entry, 'cover_url') ?? data_get($entry, 'image_url');
@endphp

@section('content')
	<div class="main-container" id="blogPostPage">
		@include('front.blog.partials.breadcrumbs')

		<div class="container mb-4">
			<div class="row">
				{{-- Post --}}
				<div class="col-12 col-lg-8">
					<article class="card border shadow-sm">
						@if (!empty($imageUrl))
							@php
								echo generateImageHtml($imageUrl, str(data_get($entry, 'title'))->slug()->toString(), null, [
									'class' => 'card-img-top w-100',
									'style' => 'max-height: 460px; object-fit: cover;',
								]);
							@endphp
						@endif

						<div class="card-body p-4">
							@if (!empty($category))
								<a href="{{ $category->url }}" class="badge bg-primary-subtle text-primary-emphasis text-decoration-none mb-2">
									{{ $category->name }}
								</a>
							@endif

							<h1 class="fs-2 fw-bold mb-3">{{ data_get($entry, 'title') }}</h1>

							<div class="d-flex flex-wrap gap-3 text-body-secondary small border-bottom pb-3 mb-4">
								<span>
									<i class="fa-regular fa-calendar"></i>
									{{ t('blog_published_on', ['date' => data_get($entry, 'published_at')?->translatedFormat('F d, Y')]) }}
								</span>
								@if (!empty($author) && !empty($author->name))
									<span>
										<i class="fa-regular fa-user"></i>
										{{ t('blog_written_by', ['author' => $author->name]) }}
									</span>
								@endif
								<span>
									<i class="fa-regular fa-clock"></i>
									{{ t('blog_min_read', ['count' => $entry->readingTime()]) }}
								</span>
								<span>
									<i class="fa-regular fa-eye"></i>
									{{ t('blog_views', ['count' => (int)data_get($entry, 'views')]) }}
								</span>
							</div>

							<div class="text-start from-wysiwyg blog-content">
								{!! data_get($entry, 'content') !!}
							</div>
						</div>
					</article>

					{{-- Social shares --}}
					@include('front.layouts.partials.social.horizontal')

					{{-- Related posts --}}
					@if ($relatedPosts->count() > 0)
						<div class="mt-4">
							<h2 class="fs-4 fw-bold mb-3">{{ t('blog_related_posts') }}</h2>
							<div class="row">
								@foreach($relatedPosts as $relatedPost)
									@include('front.blog.partials.card', [
										'post'       => $relatedPost,
										'colClass'   => 'col-md-6 col-lg-4',
										'titleTag'   => 'h3',
										'titleClass' => 'fs-6',
									])
								@endforeach
							</div>
						</div>
					@endif

					<div class="mt-3">
						<a href="{{ urlGen()->blog() }}" class="btn btn-outline-primary">
							<i class="fa-solid fa-arrow-left"></i> {{ t('blog_back_to_blog') }}
						</a>
					</div>
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
