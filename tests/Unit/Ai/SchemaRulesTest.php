<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiResult;
use App\Services\Ai\LaravelAiClient;
use App\Services\Ai\Support\SchemaRules;
use Laravel\Ai\Responses\Data\FinishReason;
use Tests\TestCase;

class SchemaRulesTest extends TestCase
{
	private array $schema = [
		'type'       => 'object',
		'required'   => ['verdict', 'categories', 'reason', 'note'],
		'properties' => [
			'verdict'    => ['type' => 'string', 'enum' => ['allow', 'reject', 'needs_human']],
			'categories' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['illegal_drugs', 'scam_fraud']]],
			'reason'     => ['type' => 'string'],
			'note'       => ['type' => ['string', 'null']],
			'score'      => ['type' => 'integer'],
		],
	];
	
	public function test_an_empty_list_and_an_empty_string_are_valid(): void
	{
		$this->assertNull(SchemaRules::firstError(
			['verdict' => 'allow', 'categories' => [], 'reason' => '', 'note' => null],
			$this->schema
		));
	}
	
	public function test_a_value_outside_the_enum_is_invalid(): void
	{
		$this->assertNotNull(SchemaRules::firstError(
			['verdict' => 'maybe', 'categories' => [], 'reason' => '', 'note' => null],
			$this->schema
		));
		$this->assertNotNull(SchemaRules::firstError(
			['verdict' => 'allow', 'categories' => ['weapons'], 'reason' => '', 'note' => null],
			$this->schema
		));
	}
	
	public function test_a_missing_required_key_is_invalid(): void
	{
		$this->assertNotNull(SchemaRules::firstError(['categories' => [], 'reason' => '', 'note' => null], $this->schema));
	}
	
	public function test_a_wrong_type_is_invalid(): void
	{
		$this->assertNotNull(SchemaRules::firstError(
			['verdict' => 'allow', 'categories' => [], 'reason' => '', 'note' => null, 'score' => 'high'],
			$this->schema
		));
	}
	
	public function test_refusals_and_truncation_are_never_treated_as_a_normal_answer(): void
	{
		$this->assertSame(AiResult::REFUSAL, LaravelAiClient::statusForFinishReason(FinishReason::ContentFilter)['status']);
		$this->assertSame(AiResult::TRUNCATED, LaravelAiClient::statusForFinishReason(FinishReason::Length)['status']);
		$this->assertSame(AiResult::ERROR, LaravelAiClient::statusForFinishReason(FinishReason::Error)['status']);
		$this->assertNull(LaravelAiClient::statusForFinishReason(FinishReason::Stop));
		$this->assertNull(LaravelAiClient::statusForFinishReason(null));
	}
}
