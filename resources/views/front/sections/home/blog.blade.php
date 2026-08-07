@php
	$sectionOptions = $blogOptions ?? [];

	$sectionTitle = $sectionOptions['title'] ?? null;
	$sectionTitle = !empty($sectionTitle) ? $sectionTitle : t('blog_latest_posts');

	$showViewMoreBtn = ($sectionOptions['show_view_more_btn'] ?? '0') == '1';

	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';

	$htmlAttr = $sectionOptions['html_attributes'] ?? '';
	$htmlAttr = !empty($htmlAttr) ? " $htmlAttr" : '';

	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';

	$sectionData ??= [];
	$posts = (array)($sectionData['posts'] ?? []);
@endphp
@if (!empty($posts))
	<div class="container{{ $cssClasses }} d-flex align-items-center" style="{!! $style !!}">
		<div class="card w-100"{!! $htmlAttr !!}>

			<div class="card-header border-bottom-0">
				<h2 class="mb-0 float-start fw-lighter fs-4">
					{!! $sectionTitle !!}
				</h2>
				@if ($showViewMoreBtn)
					<h5 class="mb-0 float-end mt-1 fs-6 fw-lighter text-uppercase">
						<a href="{{ urlGen()->blog() }}" class="{{ linkClass() }}">
							{{ t('View more') }} <i class="fa-solid fa-bars"></i>
						</a>
					</h5>
				@endif
			</div>

			<div class="card-body rounded pb-0">
				<div class="row">
					@foreach($posts as $post)
						@include('front.blog.partials.card', [
							'post'       => $post,
							'colClass'   => 'col-md-6 col-lg-4',
							'titleTag'   => 'h3',
							'titleClass' => 'fs-6',
						])
					@endforeach
				</div>
			</div>

			@if ($showViewMoreBtn)
				<div class="card-footer bg-transparent border-top-0 text-center pb-3">
					<a href="{{ urlGen()->blog() }}" class="btn btn-outline-primary">
						{{ t('blog_view_all_posts') }} <i class="fa-solid fa-arrow-right"></i>
					</a>
				</div>
			@endif

		</div>
	</div>
@endif
