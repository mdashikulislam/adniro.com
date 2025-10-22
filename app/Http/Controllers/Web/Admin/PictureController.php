<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Http\Controllers\Web\Admin;

use App\Helpers\Common\Files\FileSys;
use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\PictureRequest as StoreRequest;
use App\Http\Requests\Admin\PictureRequest as UpdateRequest;
use App\Http\Requests\Admin\Request;
use App\Models\Picture;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Throwable;

class PictureController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Picture::class);
		$this->xPanel->with([
			'post',
			'post.country',
		]);
		$this->xPanel->withoutAppends();
		$this->xPanel->setRoute(urlGen()->adminUri('pictures'));
		$this->xPanel->setEntityNameStrings(trans('admin.picture'), trans('admin.pictures'));
		$this->xPanel->removeButton('create');
		if (!request()->input('order')) {
			$this->xPanel->orderByDesc('created_at');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'edit_post', 'editPostInLineButton', 'beginning');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// Filters
			$this->xPanel->disableSearchBar();
			
			$this->xPanel->addFilter(
				options: [
					'name'        => 'country',
					'type'        => 'select2',
					'label'       => mb_ucfirst(trans('admin.country')),
					'placeholder' => trans('admin.select'),
				],
				values: getCountries(),
				filterLogic: function ($value) {
					$this->xPanel->addClause('whereHas', 'post', function ($query) use ($value) {
						$query->where('country_code', '=', $value);
					});
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'post_id',
					'type'  => 'text',
					'label' => trans('admin.Listing'),
				],
				filterLogic: function ($value) {
					if (is_numeric($value) || isHashedId($value)) {
						$value = hashId($value, true) ?? $value;
						$this->xPanel->addClause('where', 'post_id', '=', $value);
					} else {
						$this->xPanel->addClause('whereHas', 'post', function ($query) use ($value) {
							$query->where('title', 'LIKE', $value . '%');
						});
					}
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'status',
					'type'  => 'dropdown',
					'label' => trans('admin.Status'),
				],
				values: [
					1 => trans('admin.Unactivated'),
					2 => trans('admin.Activated'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', 'active', '=', 1);
					}
				}
			);
			
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'file_path',
				'label'         => trans('admin.Filename'),
				'type'          => 'model_function',
				'function_name' => 'crudFilePathColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'post_id',
				'label'         => trans('admin.Listing'),
				'type'          => 'model_function',
				'function_name' => 'crudPostTitleColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'country_code',
				'label'         => mb_ucfirst(trans('admin.country')),
				'type'          => 'model_function',
				'function_name' => 'crudCountryColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('admin.Active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$this->xPanel->addField([
				'name'  => 'post_id',
				'type'  => 'hidden',
				'value' => request()->input('post_id'),
			], 'create');
			
			$this->xPanel->addField([
				'name'   => 'file_path',
				'label'  => trans('admin.Picture'),
				'type'   => 'image',
				'upload' => true,
				'disk'   => 'public',
			]);
			
			$this->xPanel->addField([
				'name'  => 'active',
				'label' => trans('admin.Active'),
				'type'  => 'checkbox_switch',
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		try {
			$request = $this->uploadFile($request);
		} catch (Throwable $e) {
		}
		
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		try {
			$request = $this->uploadFile($request);
		} catch (Throwable $e) {
		}
		
		return parent::updateCrud($request);
	}
	
	private function uploadFile(Request $request): Request
	{
		$post = null;
		
		// From edit page
		$currentResourceId = request()->route()->parameter('picture');
		if (!empty($currentResourceId)) {
			$picture = Picture::with('post')->find($currentResourceId);
			if (!empty($picture->post)) {
				$post = $picture->post;
			}
		}
		
		// From create page
		if (empty($post)) {
			$postId = request()->input('post_id');
			if (!empty($postId)) {
				$post = Post::find($postId);
			}
		}
		
		if (!empty($post)) {
			$attribute = 'file_path';
			$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
			
			// Get uploaded image file (should return an UploadedFile object)
			$file = $request->file($attribute, $request->input($attribute));
			
			if (!empty($file)) {
				// Upload the image & get its local path
				$filePath = Upload::image($file, $destPath, null, true);
				
				// Add the mime type in the input (to save it in the database)
				$mimeType = FileSys::getMimeType($file);
				
				// Set the local path in the input
				$request->merge([
					$attribute  => $filePath,
					'mime_type' => $mimeType,
				]);
			}
		}
		
		return $request;
	}
}
