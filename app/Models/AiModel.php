<?php

namespace App\Models;

use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Services\Ai\Support\AdminHtml;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * An AI model of a provider, managed from the admin panel (adniro:ai).
 */

class AiModel extends BaseModel
{
	use Crud;
	
	protected $table = 'ai_models';
	
	protected $guarded = ['id'];
	
	protected $fillable = [
		'ai_provider_id',
		'model_id',
		'label',
		'supports_vision',
		'supports_structured_output',
		'supports_transcription',
		'input_price_per_mtok',
		'output_price_per_mtok',
		'options',
		'active',
	];
	
	protected function casts(): array
	{
		return [
			'supports_vision'            => 'boolean',
			'supports_structured_output' => 'boolean',
			'supports_transcription'     => 'boolean',
			'input_price_per_mtok'       => 'decimal:4',
			'output_price_per_mtok'      => 'decimal:4',
			'active'                     => 'boolean',
			'last_test_ok'               => 'boolean',
			'last_tested_at'             => 'datetime',
			'moderation_eval_passed_at'  => 'datetime',
			'listing_assistant_eval_passed_at'   => 'datetime',
		];
	}
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	
	public function displayName(): string
	{
		return !empty($this->label) ? $this->label : $this->model_id;
	}
	
	/**
	 * "Provider: Label (model-id)", used in the model pickers.
	 */
	public function fullName(): string
	{
		$provider = $this->provider?->name ?? '?';
		$name = $this->displayName();
		
		return ($name === $this->model_id)
			? $provider . ': ' . $name
			: $provider . ': ' . $name . ' (' . $this->model_id . ')';
	}
	
	/**
	 * Provider-specific request options (for example reasoning effort), as an array.
	 */
	public function optionsArray(): array
	{
		$options = $this->options;
		if (empty($options)) {
			return [];
		}
		
		$decoded = json_decode((string)$options, true);
		
		return is_array($decoded) ? $decoded : [];
	}
	
	public function hasPrices(): bool
	{
		return !is_null($this->input_price_per_mtok) && !is_null($this->output_price_per_mtok);
	}
	
	/**
	 * Active model of an active provider.
	 */
	public function isUsable(): bool
	{
		return $this->active && $this->provider && $this->provider->active;
	}
	
	/**
	 * Can this model be assigned to the given feature?
	 */
	public function isEligibleFor(string $feature): bool
	{
		$rules = config('ai_features.features.' . $feature);
		if (empty($rules) || !$this->isUsable()) {
			return false;
		}
		
		if (!empty($rules['vision']) && !$this->supports_vision) {
			return false;
		}
		
		if (!empty($rules['transcription'])) {
			return (bool)$this->supports_transcription;
		}
		
		return $this->supports_structured_output && $this->last_test_ok === true;
	}
	
	/*
	|--------------------------------------------------------------------------
	| ADMIN PANEL COLUMNS AND BUTTONS
	|--------------------------------------------------------------------------
	*/
	
	public function crudNameColumn(): string
	{
		$out = '<strong>' . e($this->displayName()) . '</strong>';
		if ($this->displayName() !== $this->model_id) {
			$out .= '<br><code>' . e($this->model_id) . '</code>';
		}
		
		return $out;
	}
	
	public function crudProviderColumn(): string
	{
		return e($this->provider?->name ?? '?');
	}
	
	public function crudCapabilitiesColumn(): string
	{
		$badges = [];
		if ($this->supports_vision) {
			$badges[] = '<span class="badge bg-info text-dark">' . e(trans('ai.cap_vision')) . '</span>';
		}
		if ($this->supports_structured_output) {
			$badges[] = '<span class="badge bg-secondary">' . e(trans('ai.cap_json')) . '</span>';
		}
		if ($this->supports_transcription) {
			$badges[] = '<span class="badge bg-secondary">' . e(trans('ai.cap_transcription')) . '</span>';
		}
		
		return implode(' ', $badges);
	}
	
	public function crudPricesColumn(): string
	{
		if (!$this->hasPrices()) {
			return '<span class="text-muted">' . e(trans('ai.price_unknown')) . '</span>';
		}
		
		return e('$' . rtrim(rtrim((string)$this->input_price_per_mtok, '0'), '.') . ' / $' . rtrim(rtrim((string)$this->output_price_per_mtok, '0'), '.'));
	}
	
	/**
	 * Last passed evaluations: "php artisan ai:moderation-eval" and "php artisan ai:listing-assistant-eval".
	 */
	public function crudEvaluationsColumn(): string
	{
		$badge = function (string $label, $passedAt): string {
			return $passedAt
				? '<span class="badge bg-success" title="' . e($passedAt->format('Y-m-d H:i')) . '">' . e($label) . ' ✓</span>'
				: '<span class="badge bg-light text-dark border">' . e($label) . ' –</span>';
		};
		
		return $badge(trans('ai.eval_moderation'), $this->moderation_eval_passed_at)
			. ' ' . $badge(trans('ai.eval_listing_assistant'), $this->listing_assistant_eval_passed_at);
	}
	
	public function crudStatusColumn(): string
	{
		return AdminHtml::statusBadge($this->isUsable(), $this->last_test_ok, $this->last_tested_at, $this->last_test_error);
	}
	
	public function testButton($xPanel = null): string
	{
		$url = urlGen()->adminUrl('ai_models/' . $this->id . '/test');
		
		return AdminHtml::postButton($url, trans('ai.test_model'), 'fa-solid fa-vial');
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	
	public function provider(): BelongsTo
	{
		return $this->belongsTo(AiProvider::class, 'ai_provider_id');
	}
}
