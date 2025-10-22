<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

trait SaveActionsOperation
{
	/**
	 * Get save actions, with pre-selected action from stored session variable or config fallback.
	 *
	 * @return array
	 */
	public function getSaveAction(): array
	{
		$defaultSaveAction = config('larapen.admin.default_save_action', 'save_and_back');
		$saveAction = session('save_action', $defaultSaveAction);
		
		// Permissions and their related actions.
		$permissions = [
			'list'   => 'save_and_back',
			'update' => 'save_and_edit',
			'create' => 'save_and_new',
		];
		
		$saveOptions = collect($permissions)
			// Restrict list to allowed actions.
			->filter(function ($action, $permission) {
				return $this->xPanel->hasAccess($permission);
			})
			// Generate list of possible actions.
			->mapWithKeys(function ($action, $permission) {
				return [$action => $this->getSaveActionButtonName($action)];
			})->toArray();
		
		// Set current action if it exists, or first available option.
		if (isset($saveOptions[$saveAction])) {
			$saveCurrent = [
				'value' => $saveAction,
				'label' => $saveOptions[$saveAction],
			];
		} else {
			$saveCurrent = [
				'value' => key($saveOptions),
				'label' => reset($saveOptions),
			];
		}
		
		// Remove active action from options.
		unset($saveOptions[$saveCurrent['value']]);
		
		return [
			'active'  => $saveCurrent,
			'options' => $saveOptions,
		];
	}
	
	/**
	 * Change the session variable that remembers what to do after the "Save" action.
	 *
	 * @param string|null $forceSaveAction
	 * @return void
	 */
	public function setSaveAction(?string $forceSaveAction = null)
	{
		if (!empty($forceSaveAction)) {
			$saveAction = $forceSaveAction;
		} else {
			$defaultSaveAction = config('larapen.admin.default_save_action', 'save_and_back');
			$saveAction = request()->input('save_action', $defaultSaveAction);
		}
		
		if (config('larapen.admin.show_save_action_change', true)) {
			if (session('save_action', 'save_and_back') !== $saveAction) {
				$message = trans('admin.save_action_changed_notification');
				notification($message, 'info');
			}
		}
		
		session()->put('save_action', $saveAction);
	}
	
	/**
	 * Redirect to the correct URL, depending on which save action has been selected.
	 *
	 * @param string|null $itemId
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function performSaveAction(?string $itemId = null)
	{
		$defaultSaveAction = config('larapen.admin.default_save_action', 'save_and_back');
		$saveAction = request()->input('save_action', $defaultSaveAction);
		
		switch ($saveAction) {
			case 'save_and_new':
				$redirectUrl = $this->xPanel->getUrl('create');
				break;
			case 'save_and_edit':
				$itemId = !empty($itemId) ? $itemId : request()->input('id');
				$redirectUrl = $this->xPanel->getUrl($itemId . '/edit');
				
				$locale = request()->input('locale');
				if (!empty($locale)) {
					$redirectUrl = urlBuilder($redirectUrl)->setParameters(['locale' => $locale])->toString();
				}
				
				$currentTab = request()->input('current_tab');
				if (!empty($currentTab)) {
					$redirectUrl = urlBuilder($redirectUrl)->setFragment($currentTab)->toString();
				}
				break;
			case 'save_and_back':
			default:
				$redirectUrl = $this->xPanel->getUrl();
				break;
		}
		
		// If the request is AJAX, return a JSON response
		if (request()->ajax()) {
			return response()->json([
				'success'      => true,
				'data'         => $this->xPanel->entry,
				'redirect_url' => $redirectUrl,
			]);
		}
		
		return redirect()->to($redirectUrl);
	}
	
	/**
	 * Get the translated text for the Save button.
	 *
	 * @param string|null $actionValue
	 * @return string
	 */
	private function getSaveActionButtonName(?string $actionValue = 'save_and_back'): string
	{
		$name = match ($actionValue) {
			'save_and_edit' => trans('admin.save_action_save_and_edit'),
			'save_and_new'  => trans('admin.save_action_save_and_new'),
			default         => trans('admin.save_action_save_and_back'),
		};
		
		return castToString($name, 'Save');
	}
}
