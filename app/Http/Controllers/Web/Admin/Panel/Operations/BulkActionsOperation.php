<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

trait BulkActionsOperation
{
	private array $allowedActions = ['deletion', 'activation', 'deactivation', 'approval', 'disapproval'];
	
	/**
	 * Bulk Actions
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function bulkActions(): JsonResponse|RedirectResponse
	{
		$action = request()->input('action');
		
		if (!in_array($action, $this->allowedActions)) {
			return $this->notAllowedAction();
		}
		
		return match ($action) {
			'deletion'     => $this->bulkDeletion(),
			'activation'   => $this->bulkActivation(),
			'deactivation' => $this->bulkDeactivation(),
			'approval'     => $this->bulkApproval(),
			'disapproval'  => $this->bulkDisapproval(),
			default        => $this->notAllowedAction(),
		};
	}
	
	// PRIVATE
	
	/**
	 * Bulk Deletion
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function bulkDeletion(): JsonResponse|RedirectResponse
	{
		$this->xPanel->hasAccessOrFail('delete');
		
		return $this->_bulkActions('deletion', null, null);
	}
	
	/**
	 * Bulk Activation
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function bulkActivation(): JsonResponse|RedirectResponse
	{
		$this->xPanel->hasAccessOrFail('update');
		
		return $this->_bulkActions('activation', 'active', 1);
	}
	
	/**
	 * Bulk Deactivation
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function bulkDeactivation(): JsonResponse|RedirectResponse
	{
		$this->xPanel->hasAccessOrFail('update');
		
		return $this->_bulkActions('deactivation', 'active', 0);
	}
	
	/**
	 * Bulk Approval (Reviewed)
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function bulkApproval(): JsonResponse|RedirectResponse
	{
		if (!config('settings.listing_form.listings_review_activation')) {
			return $this->notAllowedAction();
		}
		
		$this->xPanel->hasAccessOrFail('update');
		
		return $this->_bulkActions('approval', 'reviewed_at', now());
	}
	
	/**
	 * Bulk Disapproval (Not Reviewed)
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function bulkDisapproval(): JsonResponse|RedirectResponse
	{
		if (!config('settings.listing_form.listings_review_activation')) {
			return $this->notAllowedAction();
		}
		
		$this->xPanel->hasAccessOrFail('update');
		
		return $this->_bulkActions('disapproval', 'reviewed_at', null);
	}
	
	/**
	 * Bulk Boolean Column Update
	 *
	 * @param $action
	 * @param null $column
	 * @param null $value
	 * @param null $successMessageKey
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function _bulkActions($action, $column = null, $value = null, $successMessageKey = null): JsonResponse|RedirectResponse
	{
		$model = $this->xPanel->model;
		$redirectUrl = $this->xPanel->getUrl();
		
		if (
			!in_array($action, $this->allowedActions)
			|| (
				!in_array($action, ['deletion'])
				&& (!in_array($column, $model->getFillable()))
			)
		) {
			return $this->notAllowedAction();
		}
		
		$data = [];
		
		if (!request()->has('entryId')) {
			$message = trans('admin.no_item_selected');
			if (isFromAjax()) {
				$data['success'] = false;
				$data['message'] = $message;
				
				return response()->json($data, 410, [], JSON_UNESCAPED_UNICODE);
			}
			
			notification($message, 'error');
			
			return redirect()->to($redirectUrl);
		}
		
		try {
			
			// $modelKeyName = $model->getKeyName();
			$modelKeyName = 'id';
			
			$ids = request()->input('entryId');
			$ids = is_array($ids) ? $ids : [];
			
			foreach ($ids as $id) {
				$entry = $model->query()->where($modelKeyName, $id)->first();
				if (empty($entry)) continue;
				
				if ($action == 'deletion') {
					$res = $entry->delete();
				} else {
					if ($entry->{$column} != $value) {
						$entry->{$column} = $value;
						$entry->save();
					}
				}
			}
			
			if (!empty($successMessageKey)) {
				$message = trans('admin.' . $successMessageKey, ['countSelected' => count($ids)]);
			} else {
				$message = t('confirm_message_success');
			}
			
			// AJAX Response
			if (isFromAjax()) {
				$data['success'] = true;
				$data['message'] = $message;
				
				return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
			}
			
			notification($message, 'success');
			
		} catch (Throwable $e) {
			$message = $e->getMessage();
			
			// AJAX Response
			if (isFromAjax()) {
				$data['success'] = false;
				$data['message'] = $message;
				
				return response()->json($data, 410, [], JSON_UNESCAPED_UNICODE);
			}
			
			notification($message, 'error');
		}
		
		return redirect()->to($redirectUrl);
	}
	
	/**
	 * Not Allowed Action
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function notAllowedAction(): JsonResponse|RedirectResponse
	{
		$redirectUrl = $this->xPanel->getUrl();
		
		$message = 'Action not allowed.';
		if (isFromAjax()) {
			$data['success'] = false;
			$data['message'] = $message;
			
			return response()->json($data, 410, [], JSON_UNESCAPED_UNICODE);
		}
		
		notification($message, 'error');
		
		return redirect()->to($redirectUrl);
	}
}
