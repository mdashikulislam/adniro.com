<?php

namespace App\Services\ListingWriter;

/*
 * The writing-help request (adniro:ai). The instructions are one stable system block, so the
 * provider can cache it; the seller's text goes in the user message, escaped.
 */

class WritingPrompt
{
	public const VERSION = '2026-11-21.1';

	private const GOALS = [
		'title:suggest'       => 'Write a title for this listing from the seller\'s text.',
		'title:shorten'       => 'Shorten the current title. Keep the item, brand, model and the most important detail.',
		'title:clearer'       => 'Make the current title clearer and easier to scan. Keep its facts.',
		'description:suggest' => 'Write a description for this listing from the seller\'s text.',
		'description:shorten' => 'Shorten the current description. Keep every fact a buyer needs.',
		'description:clearer' => 'Make the current description clearer: fix spelling and grammar, short paragraphs. Keep its facts.',
	];

	public function instructions(): string
	{
		return rtrim((string)file_get_contents(resource_path('ai/listing-writer-instructions.md')));
	}

	public function schema(): array
	{
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => ['text'],
			'properties'           => ['text' => ['type' => 'string']],
		];
	}

	public function prompt(WritingTask $task, string $locale): string
	{
		$key = $task->field . ':' . $task->action;
		$titleMax = min((int)config('settings.listing_form.title_max_length', 150), 150);
		$descriptionMax = (int)config('settings.listing_form.description_max_length', 6000);

		return '<task>' . $key . "</task>\n"
			. '<locale>' . e($locale) . "</locale>\n"
			. '<category>' . self::escape((string)$task->categoryName) . "</category>\n"
			. '<limits>title: ' . $titleMax . ' characters; description: ' . $descriptionMax . " characters</limits>\n"
			. "<current_title>\n" . self::escape($task->title) . "\n</current_title>\n"
			. "<current_description>\n" . self::escape($task->description) . "\n</current_description>\n"
			. self::GOALS[$key];
	}

	private static function escape(string $text): string
	{
		return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
	}
}
