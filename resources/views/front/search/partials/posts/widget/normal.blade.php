@php
	$widget ??= [];
	$posts = (array)data_get($widget, 'posts');
	$totalPosts = (int)data_get($widget, 'totalPosts', 0);
	
	$sectionOptions ??= [];
	
	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';
	
	$htmlAttr = $sectionOptions['html_attributes'] ?? '';
	$htmlAttr = !empty($htmlAttr) ? " {$htmlAttr}" : '';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
	
	$gridClass = config('settings.listings_list.display_mode', 'grid-view');
@endphp
@if ($totalPosts > 0)
	<div class="container{{ $cssClasses }}" style="{!! $style !!}">
		<div class="card listing-section"{!! $htmlAttr !!}>
			<div class="card-header border-bottom-0 section-header d-flex align-items-center justify-content-between flex-wrap gap-2">
				<h4 class="mb-0 fw-lighter section-title">
					{!! data_get($widget, 'title') !!}
				</h4>
				<a href="{{ data_get($widget, 'link') }}" class="{{ linkClass() }} section-more-link">
					{{ t('View more') }} <i class="fa-solid fa-arrow-right"></i>
				</a>
			</div>
			
			<div class="card-body rounded py-0">
				@if (config('settings.listings_list.display_mode') == 'make-list')
					@include('front.search.partials.posts.template.list')
				@elseif (config('settings.listings_list.display_mode') == 'make-compact')
					@include('front.search.partials.posts.template.compact')
				@else
					@include('front.search.partials.posts.template.grid')
				@endif
				@if (data_get($sectionOptions, 'show_view_more_btn') == '1')
					<div class="row pt-2 mt-0 mb-3 listing-section-more">
						<div class="col-12 text-center">
							<a href="{{ urlGen()->searchWithoutQuery() }}" class="btn btn-primary section-cta-btn">
								<i class="bi bi-box-arrow-in-right"></i> {{ t('View more') }}
							</a>
						</div>
					</div>
				@endif
			</div>
		</div>
	</div>
@endif

@section('after_scripts')
    @parent
@endsection
