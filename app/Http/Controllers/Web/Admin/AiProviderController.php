<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\AiProviderRequest as StoreRequest;
use App\Http\Requests\Admin\AiProviderRequest as UpdateRequest;
use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiClientContract;
use Illuminate\Http\RedirectResponse;

/*
 * Admin > AI Providers (adniro:ai). Super admins only (see routes/web/ai-admin.php).
 */

class AiProviderController extends PanelController
{
	public function setup()
	{
		$this->xPanel->setModel(AiProvider::class);
		$this->xPanel->setRoute(urlGen()->adminUri('ai_providers'));
		$this->xPanel->setEntityNameStrings(trans('ai.provider'), trans('ai.providers'));
		$this->xPanel->denyAccess(['show', 'reorder', 'details_row', 'revisions']);
		$this->xPanel->orderBy('id');
		
		$this->xPanel->addButtonFromModelFunction('line', 'test', 'testButton', 'beginning');
		
		if ($this->onIndexPage) {
			$this->xPanel->addColumn(['name' => 'name', 'label' => trans('ai.col_name')]);
			$this->xPanel->addColumn([
				'name'          => 'driver',
				'label'         => trans('ai.col_driver'),
				'type'          => 'model_function',
				'function_name' => 'crudDriverColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'api_key',
				'label'         => trans('ai.col_key'),
				'type'          => 'model_function',
				'function_name' => 'crudKeyColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'models',
				'label'         => trans('ai.col_models'),
				'type'          => 'model_function',
				'function_name' => 'crudModelsColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('ai.col_status'),
				'type'          => 'model_function',
				'function_name' => 'crudStatusColumn',
			]);
		}
		
		if ($this->onCreatePage || $this->onEditPage) {
			$entry = $this->onEditPage ? $this->xPanel->getCurrentEntry() : null;
			
			$this->xPanel->addField([
				'name'    => 'name',
				'label'   => trans('ai.field_name'),
				'type'    => 'text',
				'hint'    => trans('ai.field_name_hint'),
				'wrapper' => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'    => 'driver',
				'label'   => trans('ai.field_driver'),
				'type'    => 'select2_from_array',
				'options' => collect((array)config('ai_features.drivers'))->map(fn ($driver) => $driver['label'])->toArray(),
				'hint'    => trans('ai.field_driver_hint'),
				'wrapper' => ['class' => 'col-md-6'],
			]);
			$this->xPanel->addField([
				'name'       => 'api_key',
				'label'      => trans('ai.field_api_key'),
				'type'       => 'text',
				'attributes' => ['autocomplete' => 'off', 'spellcheck' => 'false'],
				'hint'       => trans('ai.field_api_key_hint'),
				'wrapper'    => ['class' => 'col-md-12'],
			]);
			$this->xPanel->addField([
				'name'    => 'base_url',
				'label'   => trans('ai.field_base_url'),
				'type'    => 'text',
				'hint'    => trans('ai.field_base_url_hint'),
				'wrapper' => ['class' => 'col-md-12'],
			]);
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('ai.field_active'),
				'type'    => 'checkbox_switch',
				'default' => 1,
				'hint'    => trans('ai.field_active_hint'),
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		// The form shows the saved key: an emptied field removes it (the .env key is used then)
		$request->merge(['api_key' => trim((string)$request->input('api_key')) ?: null]);
		
		return parent::updateCrud($request);
	}
	
	public function test(int $id, AiClientContract $client): RedirectResponse
	{
		$provider = AiProvider::query()->findOrFail($id);
		$result = $client->testProvider($provider);
		
		if ($result->ok()) {
			notification(trans('ai.test_ok', [
				'provider' => $provider->name,
				'model'    => $result->model,
				'ms'       => $result->latencyMs,
			]), 'success');
		} else {
			notification(trans('ai.test_failed', ['reason' => $client->describe($result)]), 'error');
		}
		
		return redirect()->back();
	}
}
