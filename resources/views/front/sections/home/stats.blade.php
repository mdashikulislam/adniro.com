@php
	$sectionOptions = $statsOptions ?? [];
	
	$iconPosts = $sectionOptions['icon_count_listings'] ?? 'bi bi-megaphone';
	$iconUsers = $sectionOptions['icon_count_users'] ?? 'bi bi-people';
	$iconLocations = $sectionOptions['icon_count_locations'] ?? 'bi bi-geo-alt';
	$prefixPosts = $sectionOptions['prefix_count_listings'] ?? '';
	$suffixPosts = $sectionOptions['suffix_count_listings'] ?? '';
	$prefixUsers = $sectionOptions['prefix_count_users'] ?? '';
	$suffixUsers = $sectionOptions['suffix_count_users'] ?? '';
	$prefixLocations = $sectionOptions['prefix_count_locations'] ?? '';
	$suffixLocations = $sectionOptions['suffix_count_locations'] ?? '';
	$disableCounterUp = $sectionOptions['disable_counter_up'] ?? false;
	$counterUpDelay = $sectionOptions['counter_up_delay'] ?? 10;
	$counterUpTime = $sectionOptions['counter_up_time'] ?? 2000;
	
	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';
	
	$htmlAttr = $sectionOptions['html_attributes'] ?? '';
	$htmlAttr = !empty($htmlAttr) ? " {$htmlAttr}" : '';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
	
	$sectionData ??= [];
	$stats = (array)($sectionData['count'] ?? []);
	
	$statItems = [
		'listings' => [
			'icon'   => $iconPosts,
			'count'  => (int)data_get($stats, 'posts'),
			'prefix' => $prefixPosts,
			'suffix' => $suffixPosts,
			'label'  => t('classified_ads'),
		],
		'users' => [
			'icon'   => $iconUsers,
			'count'  => (int)data_get($stats, 'users'),
			'prefix' => $prefixUsers,
			'suffix' => $suffixUsers,
			'label'  => t('Trusted Sellers'),
		],
		'locations' => [
			'icon'   => $iconLocations,
			'count'  => (int)data_get($stats, 'locations'),
			'prefix' => $prefixLocations,
			'suffix' => $suffixLocations,
			'label'  => t('locations'),
		],
	];
@endphp

<div class="container{{ $cssClasses }}" style="{!! $style !!}">
	<div class="card border-0 bg-body-tertiary"{!! $htmlAttr !!}>
		<div class="card-body text-secondary">
			
			<div class="row">
				@foreach($statItems as $key => $item)
					@php
						$icon = $item['icon'];
						$count = $item['count'];
						$prefix = $item['prefix'];
						$suffix = $item['suffix'];
						$label = $item['label'];
					@endphp
					<div class="col-sm-4 col-12">
						<div class="d-flex align-items-center justify-content-md-center justify-content-sm-start">
							<div class="text-end">
								<i class="{{ $icon }} fs-1"></i>
							</div>
							<div class="ms-3 text-start">
								<h5 class="fs-1 fw-bold m-0">
									@if (!empty($prefix))<span>{{ $prefix }}</span>@endif
									<span class="counter">{{ $count }}</span>
									@if (!empty($suffix))<span>{{ $suffix }}</span>@endif
								</h5>
								<div class="fs-5">{{ $label }}</div>
							</div>
						</div>
					</div>
				@endforeach
			</div>
			
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	@if (!isset($disableCounterUp) || !$disableCounterUp)
		<script>
			onDocumentReady((event) => {
				const counterUp = window.counterUp.default;
				const counterEl = document.querySelector('.counter');
				if (counterEl) {
					counterUp(counterEl, {
						duration: {{ $counterUpTime }},
						delay: {{ $counterUpDelay }}
					});
				}
			});
		</script>
	@endif
@endsection
