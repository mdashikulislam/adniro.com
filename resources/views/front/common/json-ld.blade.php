@php
	// schema.org JSON-LD (built by App\Services\StructuredDataService)
	$sdService = app(\App\Services\StructuredDataService::class);
	$jsonLdSchemas = [];

	$isListingDetailsPage = (routeActionHas('Post\Show\ShowController') && !empty($post));
	$isSearchResultsPage = (!empty($bcTab) && routeActionHas('Search\\'));
	$isHomepage = routeActionHas('Front\HomeController');

	if ($isListingDetailsPage) {
		$jsonLdSchemas[] = $sdService->forListing((array)$post, (array)($pictures ?? []), (array)($customFields ?? []));

		$bcItems = [['name' => config('country.name'), 'url' => url('/')]];
		foreach ((array)($catBreadcrumb ?? []) as $bcItem) {
			$bcItems[] = ['name' => data_get($bcItem, 'name'), 'url' => data_get($bcItem, 'url')];
		}
		$bcItems[] = ['name' => data_get($post, 'title')];
		$jsonLdSchemas[] = $sdService->breadcrumbs($bcItems);
	}

	if ($isSearchResultsPage) {
		$bcItems = [['name' => config('country.name'), 'url' => urlGen()->searchWithoutQuery()]];
		foreach ((array)$bcTab as $bcItem) {
			$bcItems[] = ['name' => data_get($bcItem, 'name'), 'url' => data_get($bcItem, 'url')];
		}
		$jsonLdSchemas[] = $sdService->breadcrumbs($bcItems);
	}

	if ($isHomepage) {
		$jsonLdSchemas[] = $sdService->organization();
		$jsonLdSchemas[] = $sdService->website();
	}

	$jsonLdSchemas = array_filter($jsonLdSchemas);
@endphp
@foreach ($jsonLdSchemas as $jsonLdSchema)
	<script type="application/ld+json">{!! json_encode($jsonLdSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
