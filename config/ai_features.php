<?php

/*
 * AI features (adniro:ai).
 *
 * Providers, API keys and models are NOT configured here: the admin manages them
 * in Admin > AI Providers / AI Models, and assigns a model to each feature in
 * Admin > Settings > AI. This file only holds technical limits.
 *
 * Note: the "ai" config key belongs to the Laravel AI SDK (laravel/ai).
 */

return [
	
	// Queue used by AI jobs (moderation). Other jobs keep QUEUE_CONNECTION.
	'queue' => [
		'connection' => env('AI_QUEUE_CONNECTION', 'redis'),
		'name'       => env('AI_QUEUE', 'ai'),
	],
	
	// HTTP timeout in seconds for one AI call
	'timeout' => (int)env('AI_HTTP_TIMEOUT', 60),
	
	// Features that can be assigned a model in Admin > Settings > AI.
	// "vision": only models that support images can be assigned.
	'features' => [
		'moderation'      => ['vision' => true, 'transcription' => false],
		'quickpost_draft' => ['vision' => true, 'transcription' => false],
		'transcription'   => ['vision' => false, 'transcription' => true],
	],
	
	// Provider drivers the admin can choose from (keys are Laravel AI SDK drivers)
	'drivers' => [
		'anthropic'         => ['label' => 'Anthropic (Claude)', 'url_required' => false],
		'openai'            => ['label' => 'OpenAI', 'url_required' => false],
		'gemini'            => ['label' => 'Google Gemini', 'url_required' => false],
		'mistral'           => ['label' => 'Mistral', 'url_required' => false],
		'groq'              => ['label' => 'Groq', 'url_required' => false],
		'xai'               => ['label' => 'xAI (Grok)', 'url_required' => false],
		'deepseek'          => ['label' => 'DeepSeek', 'url_required' => false],
		'openrouter'        => ['label' => 'OpenRouter', 'url_required' => false],
		'ollama'            => ['label' => 'Ollama (self-hosted)', 'url_required' => true],
		'openai-compatible' => ['label' => 'OpenAI-compatible (custom URL)', 'url_required' => true],
	],
	
	// Images sent to the model are resized to this long edge (px) and re-encoded as JPEG
	'image' => [
		'max_edge' => 1024,
		'quality'  => 80,
	],
	
	// Small request used by the "Test model" button and "php artisan ai:ping"
	'test' => [
		'max_tokens' => 2000,
	],
	
	// Quick post (drafting a listing from the user's text)
	'quickpost' => [
		'timeout'        => (int)env('AI_QUICKPOST_TIMEOUT', 25), // seconds, the browser waits 30
		'min_text'       => 10,
		'max_text'       => 2000,
		'max_tokens'     => 3000,
		'free_redrafts'  => 2,
		'draft_ttl'      => 48, // hours
		'rate_per_ip'    => 6,  // drafts a minute
		'rate_per_user'  => 10, // drafts a minute
	],
];
