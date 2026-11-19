<?php

namespace App\Services\Ai;

use App\Models\AiModel;
use Laravel\Ai\Files\Image;

/*
 * One structured AI request (adniro:ai).
 *
 * Feature code never names a provider or a model: the model assigned to $feature
 * in Admin > Settings > AI is used. Only the admin "Test model" action sets $model.
 */

final class AiRequest
{
	/**
	 * @param string $feature One of the keys of config('ai_features.features')
	 * @param string $instructions Stable system text. Keep it identical between calls so providers can cache it
	 * @param string $prompt The per-request text (untrusted user content goes here)
	 * @param array $schema A JSON schema of type "object" describing the expected answer
	 * @param Image[] $images Images sent with the prompt (see AiImage)
	 */
	public function __construct(
		public readonly string $feature,
		public readonly string $instructions,
		public readonly string $prompt,
		public readonly array $schema,
		public readonly array $images = [],
		public readonly int $maxTokens = 4096,
		public readonly ?int $timeoutSeconds = null,
		public readonly ?int $userId = null,
		public readonly ?string $ip = null,
		public readonly ?string $subjectType = null,
		public readonly string|int|null $subjectId = null,
		public readonly array $meta = [],
		public readonly ?AiModel $model = null,
	) {}
}
