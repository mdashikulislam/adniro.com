<?php

namespace App\Services\QuickPost;

/*
 * The drafting request (adniro:ai). The instructions and the catalog form one stable system
 * block, so the provider can cache it; the user's text goes in the user message.
 */

class DraftPrompt
{
	public const VERSION = '2026-11-20.1';
	
	public function __construct(private readonly CatalogBuilder $catalog)
	{
	}
	
	public function instructions(): string
	{
		return rtrim((string)file_get_contents(resource_path('ai/quickpost-instructions.md'))) . "\n\n" . $this->catalog->text();
	}
	
	public function prompt(string $text, string $locale, ?string $countryName, ?string $currency): string
	{
		$text = mb_substr(trim($text), 0, (int)config('ai_features.quickpost.max_text', 2000));
		
		return '<locale>' . e($locale) . "</locale>\n"
			. '<country>' . e((string)$countryName) . "</country>\n"
			. '<site_currency>' . e((string)$currency) . "</site_currency>\n"
			. "<listing_input>\n" . htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8') . "\n</listing_input>\n"
			. 'Draft the listing.';
	}
}
