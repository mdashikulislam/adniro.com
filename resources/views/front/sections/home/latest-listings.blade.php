@php
	$sectionOptions = $latestListingsOptions ?? [];
	
	$itemsInCarousel = $sectionOptions['items_in_carousel'] ?? '0';
	$isCarouselEnabled = ($itemsInCarousel == '1');
	$widgetType = $isCarouselEnabled ? 'carousel' : 'normal';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
	
	$sectionData ??= [];
	$widget = (array)($sectionData['latest'] ?? []);
@endphp
@include('front.search.partials.posts.widget.' . $widgetType, [
	'widget'         => $widget,
	'sectionOptions' => $sectionOptions
])
