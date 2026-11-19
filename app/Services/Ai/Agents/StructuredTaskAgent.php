<?php

namespace App\Services\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema as JsonSchemaContract;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\CacheInstructions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/*
 * The Laravel AI SDK agent behind every structured AI request (adniro:ai).
 *
 * - Takes a plain JSON schema and converts it to the SDK's schema types.
 * - Marks the instructions for prompt caching on providers that need an explicit marker.
 * - Passes the model's provider options (for example reasoning effort) through untouched.
 *
 * Do not add provider() or model() methods: the client passes both per call.
 */

#[CacheInstructions]
class StructuredTaskAgent implements Agent, HasStructuredOutput, HasProviderOptions
{
	use Promptable;
	
	public function __construct(
		private readonly string $instructionsText,
		private readonly array $jsonSchema,
		private readonly int $maxOutputTokens = 4096,
		private readonly array $options = [],
	) {}
	
	public function instructions(): string
	{
		return $this->instructionsText;
	}
	
	public function schema(JsonSchemaContract $schema): array
	{
		$required = (array)($this->jsonSchema['required'] ?? []);
		
		$types = [];
		foreach ((array)($this->jsonSchema['properties'] ?? []) as $name => $property) {
			$type = JsonSchema::fromArray($property);
			if (in_array($name, $required, true)) {
				$type = $type->required();
			}
			$types[$name] = $type;
		}
		
		return $types;
	}
	
	public function maxTokens(): int
	{
		return $this->maxOutputTokens;
	}
	
	public function providerOptions(Lab|string $provider): array
	{
		return $this->options;
	}
}
