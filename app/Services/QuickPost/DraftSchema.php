<?php

namespace App\Services\QuickPost;

use App\Services\Ai\Moderation\Policy;

/*
 * The answer the drafting model must return (adniro:ai).
 * Plain types only (no nulls), so every provider's structured output accepts it.
 */

final class DraftSchema
{
	public const CONFIDENCES = ['high', 'medium', 'low'];
	
	public static function schema(): array
	{
		$confidence = ['type' => 'string', 'enum' => self::CONFIDENCES];
		$text = fn () => [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => ['value', 'confidence'],
			'properties'           => ['value' => ['type' => 'string'], 'confidence' => $confidence],
		];
		
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [
				'is_listable', 'unusable_reason', 'items_detected', 'title', 'description', 'category',
				'post_type_id', 'price', 'location', 'tags', 'custom_fields', 'policy_flags',
			],
			'properties'           => [
				'is_listable'     => ['type' => 'boolean'],
				'unusable_reason' => ['type' => 'string'],
				'items_detected'  => ['type' => 'integer'],
				'title'           => $text(),
				'description'     => $text(),
				'category'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => ['id', 'confidence', 'alternatives'],
					'properties'           => [
						'id'           => ['type' => 'integer'],
						'confidence'   => $confidence,
						'alternatives' => ['type' => 'array', 'items' => ['type' => 'integer']],
					],
				],
				'post_type_id'    => ['type' => 'integer'],
				'price'           => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => ['stated', 'amount', 'currency'],
					'properties'           => [
						'stated'   => ['type' => 'boolean'],
						'amount'   => ['type' => 'number'],
						'currency' => ['type' => 'string'],
					],
				],
				'location'        => ['type' => 'string'],
				'tags'            => ['type' => 'array', 'items' => ['type' => 'string']],
				'custom_fields'   => [
					'type'  => 'array',
					'items' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => ['field_id', 'value', 'option_ids', 'confidence'],
						'properties'           => [
							'field_id'   => ['type' => 'integer'],
							'value'      => ['type' => 'string'],
							'option_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
							'confidence' => $confidence,
						],
					],
				],
				'policy_flags'    => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => array_keys(Policy::CATEGORIES)]],
			],
		];
	}
}
