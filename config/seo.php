<?php

return [

	/*
	 * Minimum number of live (verified & unarchived) listings a category or
	 * location results page must have to be indexable by search engines.
	 * Pages below this threshold get "noindex,follow" and are excluded from
	 * the XML sitemaps. They are re-included automatically once they fill up.
	 */
	'min_listings_to_index' => (int)env('SEO_MIN_LISTINGS_TO_INDEX', 3),

];
