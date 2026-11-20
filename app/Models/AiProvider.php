<?php

namespace App\Models;

use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Services\Ai\Support\AdminHtml;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * An AI provider managed from the admin panel (adniro:ai).
 * The API key is encrypted at rest and never shown back in full.
 */

class AiProvider extends BaseModel
{
	use Crud;
	
	protected $table = 'ai_providers';
	
	protected $guarded = ['id'];
	
	protected $fillable = [
		'name',
		'driver',
		'api_key',
		'base_url',
		'active',
	];
	
	/*
	 * The key is stored as plain text and shown to admins, at the owner's request (2026-11-20):
	 * encrypted keys became unreadable when APP_KEY changed. It stays out of toArray()/JSON.
	 */
	protected $hidden = ['api_key'];
	
	protected function casts(): array
	{
		return [
			'active'         => 'boolean',
			'last_test_ok'   => 'boolean',
			'last_tested_at' => 'datetime',
		];
	}
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Name under which this provider is registered in the Laravel AI SDK at runtime.
	 * It must not collide with the SDK's own provider names (anthropic, openai, ...).
	 */
	public function sdkName(): string
	{
		return 'adniro_provider_' . $this->id;
	}
	
	/**
	 * Laravel AI SDK provider configuration, built from this record.
	 */
	public function sdkConfig(): array
	{
		$config = [
			'name'   => $this->sdkName(),
			'driver' => $this->driver,
			'key'    => $this->resolvedApiKey(),
		];
		
		if (!empty($this->base_url)) {
			$config['url'] = rtrim($this->base_url, '/');
		}
		
		return $config;
	}
	
	/**
	 * The stored key, or the provider's usual environment variable as a fallback.
	 */
	public function resolvedApiKey(): string
	{
		$key = $this->readApiKey();
		if (!empty($key)) {
			return $key;
		}
		
		$envName = match ($this->driver) {
			'anthropic'         => 'ANTHROPIC_API_KEY',
			'openai'            => 'OPENAI_API_KEY',
			'gemini'            => 'GEMINI_API_KEY',
			'mistral'           => 'MISTRAL_API_KEY',
			'groq'              => 'GROQ_API_KEY',
			'xai'               => 'XAI_API_KEY',
			'deepseek'          => 'DEEPSEEK_API_KEY',
			'openrouter'        => 'OPENROUTER_API_KEY',
			'ollama'            => 'OLLAMA_API_KEY',
			'openai-compatible' => 'OPENAI_COMPATIBLE_API_KEY',
			'eleven'            => 'ELEVENLABS_API_KEY',
			default             => null,
		};
		
		return $envName ? (string)env($envName, '') : '';
	}
	
	/**
	 * The key saved in admin, or null.
	 */
	public function readApiKey(): ?string
	{
		$key = trim((string)$this->getAttribute('api_key'));
		
		return $key === '' ? null : $key;
	}
	
	public function hasApiKey(): bool
	{
		return !empty($this->resolvedApiKey());
	}
	
	/**
	 * The key as admins see it: the saved key, or where the key comes from when none is saved.
	 */
	public function displayApiKey(): string
	{
		$key = $this->readApiKey();
		if (!is_null($key)) {
			return $key;
		}
		
		return !empty($this->resolvedApiKey()) ? trans('ai.key_from_env') : trans('ai.key_missing');
	}
	
	public function driverLabel(): string
	{
		return config('ai_features.drivers.' . $this->driver . '.label', $this->driver);
	}
	
	/*
	|--------------------------------------------------------------------------
	| ADMIN PANEL COLUMNS AND BUTTONS
	|--------------------------------------------------------------------------
	*/
	
	public function crudDriverColumn(): string
	{
		return e($this->driverLabel());
	}
	
	public function crudKeyColumn(): string
	{
		$key = $this->readApiKey();
		
		return is_null($key)
			? e($this->displayApiKey())
			: '<code class="text-break user-select-all">' . e($key) . '</code>';
	}
	
	public function crudStatusColumn(): string
	{
		return AdminHtml::statusBadge($this->active, $this->last_test_ok, $this->last_tested_at, $this->last_test_error);
	}
	
	public function crudModelsColumn(): string
	{
		$count = $this->models()->count();
		$url = urlGen()->adminUrl('ai_models?provider=' . $this->id);
		
		return '<a href="' . e($url) . '">' . e(trans_choice('ai.models_count', $count, ['count' => $count])) . '</a>';
	}
	
	public function testButton($xPanel = null): string
	{
		$url = urlGen()->adminUrl('ai_providers/' . $this->id . '/test');
		
		return AdminHtml::postButton($url, trans('ai.test_connection'), 'fa-solid fa-plug');
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	
	public function models(): HasMany
	{
		return $this->hasMany(AiModel::class, 'ai_provider_id');
	}
}
