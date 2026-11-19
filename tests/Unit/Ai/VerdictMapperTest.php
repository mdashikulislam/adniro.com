<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiResult;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\Policy;
use App\Services\Ai\Moderation\VerdictMapper;
use App\Services\Ai\Support\SchemaRules;
use Tests\TestCase;

/*
 * The fixed rules that turn model labels into a moderation status (adniro:ai).
 */

class VerdictMapperTest extends TestCase
{
	public function test_a_refusal_goes_to_a_human(): void
	{
		$mapped = VerdictMapper::map(new AiResult(AiResult::REFUSAL));
		
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $mapped->status);
		$this->assertSame('refusal', $mapped->rule);
	}
	
	public function test_an_unusable_answer_goes_to_a_human(): void
	{
		foreach ([AiResult::INVALID, AiResult::TRUNCATED, AiResult::ERROR] as $status) {
			$this->assertSame(ModerationStatus::NEEDS_REVIEW, VerdictMapper::map(new AiResult($status))->status, $status);
		}
	}
	
	public function test_a_confident_reject_is_rejected_and_a_less_confident_one_goes_to_a_human(): void
	{
		$this->assertSame(ModerationStatus::REJECTED, $this->map('reject', ['illegal_drugs'], 'high')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('reject', ['illegal_drugs'], 'medium')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('reject', ['illegal_drugs'], 'low')->status);
	}
	
	public function test_review_categories_never_reject_on_their_own(): void
	{
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('reject', ['weapons_explosives'], 'high')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('allow', ['manipulation_attempt'], 'high')->status);
	}
	
	public function test_only_a_clear_allow_is_approved(): void
	{
		$this->assertSame(ModerationStatus::APPROVED, $this->map('allow', [], 'high')->status);
		$this->assertSame(ModerationStatus::APPROVED, $this->map('allow', [], 'medium')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('allow', [], 'low')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('allow', ['scam_fraud'], 'high')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('needs_human', [], 'high')->status);
		$this->assertSame(ModerationStatus::NEEDS_REVIEW, $this->map('maybe', [], 'high')->status);
	}
	
	public function test_unknown_categories_are_dropped(): void
	{
		$mapped = $this->map('allow', ['made_up', 'scam_fraud'], 'high');
		
		$this->assertSame(['scam_fraud'], $mapped->categories);
	}
	
	public function test_the_reason_is_never_shown_for_child_safety(): void
	{
		$this->assertNull($this->map('reject', ['child_safety'], 'high', 'Some detail')->userReason);
	}
	
	public function test_the_user_reason_is_cleaned(): void
	{
		$clean = VerdictMapper::cleanUserReason('<b>Drugs</b> are not allowed. See https://evil.test/x or www.evil.test, mail a@b.co or call +1 555 123 4567.');
		
		$this->assertSame('Drugs are not allowed. See or mail or call.', $clean);
		$this->assertNull(VerdictMapper::cleanUserReason('   '));
		$this->assertSame(VerdictMapper::USER_REASON_MAX, mb_strlen(VerdictMapper::cleanUserReason(str_repeat('ab ', 300))));
	}
	
	public function test_every_category_is_described_in_the_prompt_and_translated(): void
	{
		$prompt = Policy::instructions();
		
		foreach (array_keys(Policy::CATEGORIES) as $code) {
			$this->assertStringContainsString('`' . $code . '`', $prompt, $code);
			$this->assertNotSame('ai.moderation_category_' . $code, Policy::label($code), $code);
		}
	}
	
	public function test_a_well_formed_answer_passes_the_schema(): void
	{
		$answer = ['verdict' => 'reject', 'categories' => ['scam_fraud'], 'confidence' => 'high', 'user_reason' => '', 'internal_reason' => 'x'];
		
		$this->assertNull(SchemaRules::firstError($answer, Policy::schema()));
		$this->assertNotNull(SchemaRules::firstError(['categories' => ['nope']] + $answer, Policy::schema()));
	}
	
	private function map(string $verdict, array $categories, string $confidence, string $reason = 'Reason.')
	{
		return VerdictMapper::map(new AiResult(AiResult::OK, [
			'verdict'         => $verdict,
			'categories'      => $categories,
			'confidence'      => $confidence,
			'user_reason'     => $reason,
			'internal_reason' => 'note',
		]));
	}
}
