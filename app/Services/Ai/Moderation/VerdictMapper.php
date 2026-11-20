<?php

namespace App\Services\Ai\Moderation;

use App\Services\Ai\AiResult;
use App\Services\Ai\ContactStripper;

/*
 * Turns a model's labels into a moderation status with fixed rules (adniro:ai).
 * The model only labels; PHP decides. Nothing is approved unless the model clearly allowed it.
 */

final class VerdictMapper
{
	public const USER_REASON_MAX = 300;
	
	public static function map(AiResult $result): MappedVerdict
	{
		// 1. Refusal or safety block, 2. truncated or invalid answer (the client already retried once)
		if ($result->status === AiResult::REFUSAL) {
			return new MappedVerdict(ModerationStatus::NEEDS_REVIEW, rule: 'refusal');
		}
		if (!$result->ok() || !is_array($result->data)) {
			return new MappedVerdict(ModerationStatus::NEEDS_REVIEW, rule: 'no_answer_' . $result->status);
		}
		
		$data = $result->data;
		$verdict = in_array($data['verdict'] ?? null, Policy::VERDICTS, true) ? $data['verdict'] : null;
		$confidence = in_array($data['confidence'] ?? null, Policy::CONFIDENCES, true) ? $data['confidence'] : 'low';
		$categories = array_values(array_unique(array_filter(
			(array)($data['categories'] ?? []),
			fn ($code) => is_string($code) && Policy::severity($code) !== null
		)));
		
		$userReason = in_array('child_safety', $categories, true)
			? null
			: self::cleanUserReason($data['user_reason'] ?? null);
		$internalReason = mb_substr(trim(strip_tags((string)($data['internal_reason'] ?? ''))), 0, 1000) ?: null;
		
		$make = fn (string $status, string $rule) => new MappedVerdict(
			$status, $verdict, $categories, $confidence, $userReason, $internalReason, $rule
		);
		
		$hasReject = (bool)array_filter($categories, fn ($code) => Policy::severity($code) === Policy::REJECT);
		$hasReview = (bool)array_filter($categories, fn ($code) => Policy::severity($code) === Policy::REVIEW);
		
		// 3. Reject-severity category with a "reject" verdict: rejected only when confident
		if ($hasReject && $verdict === 'reject') {
			return $confidence === 'high'
				? $make(ModerationStatus::REJECTED, 'reject_high_confidence')
				: $make(ModerationStatus::NEEDS_REVIEW, 'reject_low_confidence');
		}
		
		// 4. Any human-review category, any other category, or a "needs_human" verdict
		if ($hasReview || $hasReject || $verdict === 'needs_human') {
			return $make(ModerationStatus::NEEDS_REVIEW, 'needs_human');
		}
		
		// 5. Allowed with no categories and enough confidence
		if ($verdict === 'allow' && in_array($confidence, ['high', 'medium'], true)) {
			return $make(ModerationStatus::APPROVED, 'allow');
		}
		
		return $make(ModerationStatus::NEEDS_REVIEW, 'unclear');
	}
	
	/**
	 * The reason is model output shown to a person: no tags, links, emails or phone numbers, and short.
	 * It is still escaped wherever it is displayed.
	 */
	public static function cleanUserReason(?string $text): ?string
	{
		$text = ContactStripper::strip(strip_tags((string)$text));
		$text = trim(preg_replace('~\s+~u', ' ', $text) ?? '');
		$text = preg_replace('~\s+([.,;:!?])~u', '$1', $text) ?? '';
		
		if ($text === '') {
			return null;
		}
		
		return mb_strlen($text) > self::USER_REASON_MAX
			? rtrim(mb_substr($text, 0, self::USER_REASON_MAX - 1)) . '…'
			: $text;
	}
}
