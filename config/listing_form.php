<?php

/*
 * adniro: the create/edit listing pages.
 */

return [
	// The redesigned layout (section cards, side panel, phone bar, inline checks).
	// Set LISTING_FORM_V2=false in .env to bring back the stock layout at once.
	'v2' => (bool)env('LISTING_FORM_V2', true),
];
