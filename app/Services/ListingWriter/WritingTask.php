<?php

namespace App\Services\ListingWriter;

use InvalidArgumentException;

/*
 * One request for writing help (adniro:ai): which field, what to do, and the seller's text.
 * The text is untrusted; it is turned into plain text here and escaped in the prompt.
 */

final readonly class WritingTask
{
	public const FIELDS = ['title', 'description'];

	/** suggest: write it from what the seller has; shorten: same facts, fewer words; clearer: easier to read */
	public const ACTIONS = ['suggest', 'shorten', 'clearer'];

	private function __construct(
		public string $field,
		public string $action,
		public string $title,
		public string $description,
		public ?string $categoryName,
	)
	{
	}

	public static function make(string $field, string $action, ?string $title, ?string $description, ?string $categoryName): self
	{
		if (!in_array($field, self::FIELDS, true) || !in_array($action, self::ACTIONS, true)) {
			throw new InvalidArgumentException('Unknown writing task: ' . $field . ':' . $action);
		}

		return new self(
			$field,
			$action,
			self::plainLine((string)$title),
			self::plainText((string)$description),
			($categoryName !== null && trim($categoryName) !== '') ? self::plainLine($categoryName) : null,
		);
	}

	/**
	 * The text the model works from: the field itself to shorten or clarify it; for a suggestion,
	 * everything the seller has written so far.
	 */
	public function source(): string
	{
		if ($this->action !== 'suggest') {
			return $this->field === 'title' ? $this->title : $this->description;
		}

		return trim($this->title . "\n" . $this->description);
	}

	public function hasEnoughInput(int $min): bool
	{
		return mb_strlen($this->source()) >= $min;
	}

	private static function plainLine(string $text): string
	{
		return trim((string)preg_replace('/\s+/u', ' ', self::decode(strip_tags($text))));
	}

	/** Rich-editor HTML to text: paragraphs, line breaks and list items become new lines */
	private static function plainText(string $html): string
	{
		$text = preg_replace('~<\s*br\s*/?>|</\s*(p|div|li|h[1-6]|blockquote)\s*>~iu', "\n", $html) ?? '';
		$text = self::decode(strip_tags($text));
		$text = preg_replace("/[ \t\x{00A0}]+/u", ' ', str_replace("\r", '', $text)) ?? '';
		$text = preg_replace("/ *\n */u", "\n", $text) ?? '';

		return trim((string)preg_replace("/\n{3,}/u", "\n\n", $text));
	}

	private static function decode(string $text): string
	{
		return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}
