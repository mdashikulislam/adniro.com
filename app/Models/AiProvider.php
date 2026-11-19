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
	
	protected $hidden = ['api_key'];
	
	protected function casts(): array
	{
		return [
			'api_key'        => 'encrypted',
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
			default             => null,
		};
		
		return $envName ? (string)env($envName, '') : '';
	}
	
	/**
	 * Read the stored key. An APP_KEY change makes it unreadable: treat that as "no key".
	 */
	public function readApiKey(): ?string
	{
		try {
			return $this->api_key;
		} catch (\Throwable $e) {
			return null;
		}
	}
	
	public function hasApiKey(): bool
	{
		return !empty($this->resolvedApiKey());
	}
	
	/**
	 * The last four characters of the key, for display only.
	 */
	public function maskedApiKey(): string
	{
		$key = $this->readApiKey();
		if (empty($key)) {
			return !empty($this->resolvedApiKey()) ? trans('ai.key_from_env') : trans('ai.key_missing');
		}
		
		return '•••• ' . mb_substr($key, -4);
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
		return e($this->maskedApiKey());
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
