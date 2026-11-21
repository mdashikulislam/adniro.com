<?php

namespace App\Services\Ai\Moderation;

/*
 * Moderation policy: categories, their severity, the instructions and the answer schema (adniro:ai).
 * Shared with the listing assistant, which shows the same categories as soft warnings.
 */

final class Policy
{
	/** Change it whenever resources/ai/moderation-policy.md changes in meaning. */
	public const VERSION = '2026-11-20.1';
	
	public const REJECT = 'reject';
	public const REVIEW = 'review';
	
	/** Category code => severity */
	public const CATEGORIES = [
		'illegal_drugs'           => self::REJECT,
		'scam_fraud'              => self::REJECT,
		'counterfeit_fake_docs'   => self::REJECT,
		'stolen_goods'            => self::REJECT,
		'adult_sexual'            => self::REJECT,
		'child_safety'            => self::REJECT,
		'human_exploitation'      => self::REJECT,
		'hate_violence_extremism' => self::REJECT,
		'hacking_personal_data'   => self::REJECT,
		'weapons_explosives'      => self::REVIEW,
		'protected_wildlife'      => self::REVIEW,
		'hazardous_regulated'     => self::REVIEW,
		'spam_misleading'         => self::REVIEW,
		'manipulation_attempt'    => self::REVIEW,
	];
	
	public const VERDICTS = ['allow', 'reject', 'needs_human'];
	public const CONFIDENCES = ['high', 'medium', 'low'];
	
	public static function instructions(): string
	{
		static $text = null;
		
		return $text ??= (string)file_get_contents(resource_path('ai/moderation-policy.md'));
	}
	
	public static function schema(): array
	{
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => ['verdict', 'categories', 'confidence', 'user_reason', 'internal_reason'],
			'properties'           => [
				'verdict'         => ['type' => 'string', 'enum' => self::VERDICTS],
				'categories'      => [
					'type'  => 'array',
					'items' => ['type' => 'string', 'enum' => array_keys(self::CATEGORIES)],
				],
				'confidence'      => ['type' => 'string', 'enum' => self::CONFIDENCES],
				'user_reason'     => ['type' => 'string'],
				'internal_reason' => ['type' => 'string'],
			],
		];
	}
	
	public static function severity(string $code): ?string
	{
		return self::CATEGORIES[$code] ?? null;
	}
	
	public static function label(string $code): string
	{
		return trans('ai.moderation_category_' . $code);
	}
}
