<?php

namespace App\Notifications\Concerns;

/*
 * Mail lines are rendered as Markdown, so user text such as a listing title could turn into a
 * link or formatting. This keeps it plain (adniro:ai).
 */

trait PlainMailText
{
	protected static function plainMailText(?string $text, int $limit): string
	{
		$text = preg_replace('/[\[\]()<>*_`#|~\\\\]+/u', ' ', (string)$text) ?? '';
		$text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
		
		return (string)str($text)->limit($limit);
	}
}
