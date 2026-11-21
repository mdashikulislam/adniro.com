<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\AiModelRequest as StoreRequest;
use App\Http\Requests\Admin\AiModelRequest as UpdateRequest;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\ModelCatalogException;
use App\Services\Ai\ProviderModelCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/*
 * Admin > AI Models (adniro:ai). Super admins only (see routes/web/ai-admin.php).
 */

class AiModelController extends PanelController
{
	/**
	 * Changing any of these makes the last "Test model" result meaningless.
	 */
	private const RETEST_FIELDS = ['ai_provider_id', 'model_id', 'options', 'supports_vision', 'supports_structured_output'];
	
	public function setup()
	{
		$this->xPanel->setModel(AiModel::class);
		$this->xPanel->with(['provider']);
		$this->xPanel->setRoute(urlGen()->adminUri('ai_models'));
		$this->xPanel->setEntityNameStrings(trans('ai.model'), trans('ai.models'));
		$this->xPanel->denyAccess(['show', 'reorder', 'details_row', 'revisions']);
		$this->xPanel->orderBy('ai_provider_id')->orderBy('id');
		
		$this->xPanel->addButtonFromModelFunction('line', 'test', 'testButton', 'beginning');
		
		$providerOptions = AiProvider::query()->orderBy('name')->pluck('name', 'id')->toArray();
		
		if ($this->onIndexPage) {
			$this->xPanel->addFilter(
				options: [
					'name'  => 'provider',
					'type'  => 'dropdown',
					'label' => trans('ai.col_provider'),
				],
				values: $providerOptions,
				filterLogic: fn ($value) => $this->xPanel->addClause('where', 'ai_provider_id', '=', (int)$value)
			);
			
			$this->xPanel->addColumn([
				'name'          => 'model_id',
				'label'         => trans('ai.col_model'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'ai_provider_id',
				'label'         => trans('ai.col_provider'),
				'type'          => 'model_function',
				'function_name' => 'crudProviderColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'supports_vision',
				'label'         => trans('ai.col_capabilities'),
				'type'          => 'model_function',
				'function_name' => 'crudCapabilitiesColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'input_price_per_mtok',
				'label'         => trans('ai.col_prices'),
				'type'          => 'model_function',
				'function_name' => 'crudPricesColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'moderation_eval_passed_at',
				'label'         => trans('ai.col_evaluations'),
				'type'          => 'model_function',
				'function_name' => 'crudEvaluationsColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('ai.col_status'),
				'type'          => 'model_function',
				'function_name' => 'crudStatusColumn',
			]);
		}
		
		if ($this->onCreatePage || $this->onEditPage) {
			$this->xPanel->addField([
				'name'    => 'ai_provider_id',
				'label'   => trans('ai.field_provider'),
				'type'    => 'select2_from_array',
				'options' => $providerOptions,
				'default' => request()->integer('provider') ?: null,
				'wrapper' => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'    => 'model_id',
				'label'   => trans('ai.field_model_id'),
				'type'    => 'text',
				'hint'    => trans('ai.field_model_id_hint'),
				'wrapper' => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'    => 'model_catalog',
				'type'    => 'custom_html',
				'value'   => view('admin.ai.model-catalog')->render(),
				'wrapper' => ['class' => 'col-md-12 mb-3'],
			]);
			$this->xPanel->addField([
				'name'    => 'label',
				'label'   => trans('ai.field_label'),
				'type'    => 'text',
				'hint'    => trans('ai.field_label_hint'),
				'wrapper' => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('ai.field_active'),
				'type'    => 'checkbox_switch',
				'default' => 1,
				'hint'    => trans('ai.field_model_active_hint'),
				'wrapper' => ['class' => 'col-md-6 mt-4'],
			]);
			foreach (['supports_vision' => 1, 'supports_structured_output' => 1, 'supports_transcription' => 0] as $field => $default) {
				$this->xPanel->addField([
					'name'    => $field,
					'label'   => trans('ai.field_' . $field),
					'type'    => 'checkbox_switch',
					'default' => $default,
					'hint'    => trans('ai.field_' . $field . '_hint'),
					'wrapper' => ['class' => 'col-md-4'],
				]);
			}
			$this->xPanel->addField([
				'name'       => 'input_price_per_mtok',
				'label'      => trans('ai.field_input_price'),
				'type'       => 'number',
				'attributes' => ['min' => 0, 'step' => '0.0001'],
				'hint'       => trans('ai.field_price_hint'),
				'wrapper'    => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'       => 'output_price_per_mtok',
				'label'      => trans('ai.field_output_price'),
				'type'       => 'number',
				'attributes' => ['min' => 0, 'step' => '0.0001'],
				'wrapper'    => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'       => 'options',
				'label'      => trans('ai.field_options'),
				'type'       => 'textarea',
				'attributes' => ['rows' => 3, 'placeholder' => '{"output_config": {"effort": "low"}}'],
				'hint'       => trans('ai.field_options_hint'),
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		$model = AiModel::query()->findOrFail($request->route('ai_model'));
		$before = $model->only(self::RETEST_FIELDS);
		
		$response = parent::updateCrud($request);
		
		$model->refresh();
		if ($model->only(self::RETEST_FIELDS) != $before) {
			$model->forceFill([
				'last_test_ok'              => null,
				'last_tested_at'            => null,
				'last_test_error'           => null,
				'moderation_eval_passed_at' => null,
				'listing_assistant_eval_passed_at'  => null,
			])->save();
			notification(trans('ai.model_retest_needed'), 'warning');
		}
		
		return $response;
	}
	
	/**
	 * "Load models": the models the chosen provider offers, read from its API with the saved key.
	 */
	public function available(Request $request, ProviderModelCatalog $catalog): JsonResponse
	{
		$provider = AiProvider::query()->findOrFail($request->integer('provider'));
		
		try {
			$models = $catalog->list($provider);
		} catch (ModelCatalogException $e) {
			return response()->json(['success' => false, 'data' => null, 'error' => $e->getMessage()], 422);
		}
		
		return response()->json([
			'success' => true,
			'data'    => $models,
			'error'   => null,
			'meta'    => ['total' => count($models)],
		]);
	}
	
	public function test(int $id, AiClientContract $client): RedirectResponse
	{
		$model = AiModel::query()->with('provider')->findOrFail($id);
		$result = $client->testModel($model);
		
		if ($result->ok()) {
			notification(trans('ai.test_ok', [
				'provider' => $model->provider?->name,
				'model'    => $model->model_id,
				'ms'       => $result->latencyMs,
			]), 'success');
		} else {
			notification(trans('ai.test_failed', ['reason' => $client->describe($result)]), 'error');
		}
		
		return redirect()->back();
	}
}
