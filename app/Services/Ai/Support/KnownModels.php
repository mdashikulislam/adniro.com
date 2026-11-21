<?php

namespace App\Services\Ai\Support;

/*
 * What we know about well-known models, used to pre-fill the AI model form after "Load models"
 * (adniro:ai). The admin can change every value before saving.
 *
 * Prices come from each provider's own published price list (standard tier, checked
 * 2026-11-21): Anthropic, and OpenAI (developers.openai.com/api/docs/pricing). Providers
 * change prices, so re-check this table now and then. Other providers' prices are typed by the
 * admin. Some models cost more for very long prompts (Claude Haiku 5.5 above 100K tokens,
 * GPT-5.4/5.5 above 272K); adniro's prompts stay far below that. GPT-5.6 Sol is left out
 * while its promotional price runs (until 2026-11-30).
 */

final class KnownModels
{
	/**
	 * Model ID => [input $/MTok, output $/MTok]. A dated ID (claude-…-20251001,
	 * gpt-…-2024-08-06) matches its family.
	 */
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
		
		'gpt-6.2-astra'          => [8.00, 40.00],
		'gpt-6.2-sol'            => [2.00, 10.00],
		'gpt-6-astra'            => [10.00, 50.00],
		'gpt-6.1-sol'            => [2.00, 10.00],
		'gpt-6.1-terra'          => [1.00, 5.00],
		'gpt-6-luna'             => [0.10, 0.50],
		'gpt-6-sol'              => [2.00, 10.00],
		'gpt-5.6-terra'          => [2.00, 12.00],
		'gpt-5.6-luna'           => [0.20, 1.20],
		'gpt-5.5'                => [5.00, 30.00],
		'gpt-5.5-pro'            => [30.00, 180.00],
		'gpt-5.4'                => [2.50, 15.00],
		'gpt-5.4-pro'            => [30.00, 180.00],
		'gpt-5.4-mini'           => [0.75, 4.50],
		'gpt-5.4-nano'           => [0.20, 1.25],
		'gpt-5.3-codex'          => [1.75, 14.00],
		'gpt-5.2'                => [1.75, 14.00],
		'gpt-5.2-pro'            => [21.00, 168.00],
		'gpt-5.1'                => [1.25, 10.00],
		'gpt-5'                  => [1.25, 10.00],
		'gpt-5-mini'             => [0.25, 2.00],
		'gpt-5-nano'             => [0.05, 0.40],
		'gpt-5-pro'              => [15.00, 120.00],
		'gpt-4.1'                => [2.00, 8.00],
		'gpt-4.1-mini'           => [0.40, 1.60],
		'gpt-4o'                 => [2.50, 10.00],
		'gpt-4o-mini'            => [0.15, 0.60],
		'o3-pro'                 => [20.00, 80.00],
		'o3'                     => [2.00, 8.00],
		'gpt-4o-transcribe'      => [2.50, 10.00],
		'gpt-4o-mini-transcribe' => [1.25, 5.00],
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
		$family = preg_replace('/-(\d{8}|\d{4}-\d{2}-\d{2})$/', '', $id);

		return self::PRICES[$family] ?? null;
	}
}
