@php
	use Illuminate\Support\Carbon;

	/** @var \App\Models\BlogPost|array $post */
	$post ??= null;
	$colClass ??= 'col-md-6 col-lg-4';
	$titleTag ??= 'h2';
	$titleClass ??= 'fs-5';

	$postTitle = (string)data_get($post, 'title');
	$postUrl = data_get($post, 'url');
	$imageUrl = data_get($post, 'thumbnail_url') ?? data_get($post, 'image_url');
	$categoryName = data_get($post, 'category.name');
	$categoryUrl = data_get($post, 'category.url');
	$excerpt = strip_tags((string)data_get($post, 'excerpt'));

	$publishedAtLabel = null;
	$publishedAt = data_get($post, 'published_at');
	if (!empty($publishedAt)) {
		try {
			$publishedAtLabel = Carbon::parse($publishedAt)->translatedFormat('M d, Y');
		} catch (\Throwable $e) {
		}
	}

	$readingTime = (is_object($post) && method_exists($post, 'readingTime')) ? $post->readingTime() : null;
@endphp
@if (!empty($postTitle))
	<div class="{{ $colClass }} mb-4">
		<article class="card h-100 border rounded shadow-sm hover-shadow blog-card">
			@if (!empty($imageUrl))
				<a href="{{ $postUrl }}" class="d-block overflow-hidden rounded-top">
					@php
						echo generateImageHtml($imageUrl, str($postTitle)->slug()->toString(), null, [
							'class'   => 'card-img-top w-100',
							'style'   => 'aspect-ratio: 4 / 3; object-fit: cover;',
							'loading' => 'lazy',
						]);
					@endphp
				</a>
			@endif
			<div class="card-body d-flex flex-column">
				@if (!empty($categoryName))
					<div class="mb-2">
						<a href="{{ $categoryUrl }}" class="badge bg-primary-subtle text-primary-emphasis text-decoration-none">
							{{ $categoryName }}
						</a>
					</div>
				@endif

				<{{ $titleTag }} class="card-title {{ $titleClass }} fw-bold">
					<a href="{{ $postUrl }}" class="{{ linkClass('body-emphasis') }}">
						{{ str($postTitle)->limit(80) }}
					</a>
				</{{ $titleTag }}>

				@if (!empty($excerpt))
					<p class="card-text text-body-secondary small flex-grow-1">
						{{ str($excerpt)->limit(120) }}
					</p>
				@endif

				<div class="d-flex justify-content-between align-items-center small text-body-secondary mt-2">
					@if (!empty($publishedAtLabel))
						<span>
							<i class="fa-regular fa-calendar"></i> {{ $publishedAtLabel }}
						</span>
					@endif
					@if (!empty($readingTime))
						<span>
							<i class="fa-regular fa-clock"></i> {{ t('blog_min_read', ['count' => $readingTime]) }}
						</span>
					@endif
				</div>
			</div>
		</article>
	</div>
@endif
