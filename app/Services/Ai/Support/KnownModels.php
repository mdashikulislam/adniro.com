<?php

namespace App\Services\Ai\Support;

/*
 * What we know about well-known models, used to pre-fill the AI model form after "Load models"
 * (adniro:ai). The admin can change every value before saving.
 *
 * Prices are only listed where they come from the provider's own published price list
 * (Anthropic, checked 2026-11). Other providers' prices change too often to hard-code: the
 * admin types them from the provider's pricing page. Claude Haiku 5.5 costs more above 100K
 * prompt tokens; adniro's prompts stay far below that.
 */

final class KnownModels
{
	/** Model ID => [input $/MTok, output $/MTok]. A dated ID (…-20251001) matches its family. */
	private const PRICES = [
		'claude-fable-5-2'  => [8.00, 40.00],
		'claude-mythos-5-2' => [8.00, 40.00],
		'claude-fable-5-1'  => [10.00, 50.00],
		'claude-mythos-5-1' => [10.00, 50.00],
		'claude-fable-5'    => [10.00, 50.00],
		'claude-opus-5-5'   => [4.00, 20.00],
		'claude-opus-5'     => [5.00, 25.00],
		'claude-opus-4-8'   => [5.00, 25.00],
		'claude-opus-4-7'   => [5.00, 25.00],
		'claude-opus-4-6'   => [5.00, 25.00],
		'claude-sonnet-5-5' => [2.00, 10.00],
		'claude-sonnet-5'   => [2.00, 10.00],
		'claude-sonnet-4-6' => [3.00, 15.00],
		'claude-haiku-5-5'  => [0.10, 0.50],
		'claude-haiku-4-5'  => [1.00, 5.00],
	];

	/**
	 * Model families whose current models read images and return structured output.
	 * Only used when the provider's API does not say.
	 */
	private const VISION_AND_STRUCTURED = [
		'/^claude-(fable|mythos|opus|sonnet|haiku)-/',
		'/^gemini-\d/',
		'/^gpt-(4o|4\.1|[5-9])/',
		'/^o[3-9](-|$)/',
	];

	/** Speech-to-text models. */
	private const TRANSCRIPTION = '/(whisper|transcribe|scribe|voxtral)/i';

	/**
	 * Fills the values the provider's API left unknown (null).
	 *
	 * @param array{id: string, vision: ?bool, structured: ?bool, transcription: ?bool, input_price: ?float, output_price: ?float} $model
	 */
	public static function complete(array $model): array
	{
		$id = strtolower($model['id']);
		$isTranscription = preg_match(self::TRANSCRIPTION, $id) === 1;
		$isKnownFamily = !$isTranscription && self::matchesFamily($id);

		$model['transcription'] ??= $isTranscription ? true : ($isKnownFamily ? false : null);
		$model['vision'] ??= $isKnownFamily ? true : ($isTranscription ? false : null);
		$model['structured'] ??= $isKnownFamily ? true : ($isTranscription ? false : null);

		$price = self::price($id);
		$model['input_price'] ??= $price[0] ?? null;
		$model['output_price'] ??= $price[1] ?? null;

		return $model;
	}

	private static function matchesFamily(string $id): bool
	{
		foreach (self::VISION_AND_STRUCTURED as $pattern) {
			if (preg_match($pattern, $id) === 1) {
				return true;
			}
		}

		return false;
	}

	/** @return array{0: float, 1: float}|null */
	private static function price(string $id): ?array
	{
		$family = preg_replace('/-\d{8}$/', '', $id);

		return self::PRICES[$family] ?? null;
	}
}
