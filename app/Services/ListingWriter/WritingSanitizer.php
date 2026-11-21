<?php

namespace App\Services\ListingWriter;

use App\Services\ListingAssistant\DraftSanitizer;

/*
 * The model's text is untrusted (adniro:ai): plain text only, no links, emails or phone numbers,
 * and within the site's length limits. An empty result means the answer cannot be used.
 */

class WritingSanitizer
{
	private const QUOTES = "\"'“”‘’«»„";

	public function sanitize(string $field, string $text): string
	{
		if ($field === 'title') {
			$max = min((int)config('settings.listing_form.title_max_length', 150), 150);
			$title = DraftSanitizer::clean($text, PHP_INT_MAX, true);

			return mb_substr(trim($title, self::QUOTES . ' '), 0, $max);
		}

		return DraftSanitizer::clean($text, (int)config('settings.listing_form.description_max_length', 6000));
	}
}
