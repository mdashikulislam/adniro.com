<?php

return [

	/*
	 * Minimum number of live (verified & unarchived) listings a category or
	 * location results page must have to be indexable by search engines.
	 * Pages below this threshold get "noindex,follow" and are excluded from
	 * the XML sitemaps. They are re-included automatically once they fill up.
	 */
	'min_listings_to_index' => (int)env('SEO_MIN_LISTINGS_TO_INDEX', 3),

	/*
	 * Public URL of a seller's listings page. It has no country segment: the page lists
	 * the seller's live listings from every country.
	 * It is deliberately kept out of "config/routes.php": the first segment of every route
	 * of that file becomes a banned prefix for the listings' slugs (regexSimilarRoutesPrefixes()),
	 * which would change the URL of every listing whose title starts with "user".
	 */
	'user_route' => 'user/{username}',

];
