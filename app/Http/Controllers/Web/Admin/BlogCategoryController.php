<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Http\Controllers\Web\Admin;

use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\BlogCategoryRequest as StoreRequest;
use App\Http\Requests\Admin\BlogCategoryRequest as UpdateRequest;
use App\Http\Requests\Admin\Request;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Throwable;

class BlogCategoryController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(BlogCategory::class);
		$this->xPanel->setRoute(urlGen()->adminUri('blog/categories'));
		$this->xPanel->setEntityNameStrings(trans('admin.blog_category'), trans('admin.blog_categories'));
		$this->xPanel->enableReorder('name', 1);
		$this->xPanel->allowAccess(['reorder']);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}

		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');

		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// FILTERS
			$this->xPanel->disableSearchBar();

			$this->xPanel->addFilter(
				options: [
					'name'  => 'name',
					'type'  => 'text',
					'label' => mb_ucfirst(trans('admin.Name')),
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', 'name', 'LIKE', "%$value%");
				}
			);

			$this->xPanel->addFilter(
				options: [
					'name'  => 'status',
					'type'  => 'dropdown',
					'label' => trans('admin.Status'),
				],
				values: [
					1 => trans('admin.Activated'),
					2 => trans('admin.Unactivated'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', 'active', '=', 1);
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
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
				'name'          => 'name',
				'label'         => trans('admin.Name'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
			]);

			$this->xPanel->addColumn([
				'name'  => 'slug',
				'label' => trans('admin.Slug'),
			]);

			$this->xPanel->addColumn([
				'name'          => 'posts_count',
				'label'         => trans('admin.blog_posts'),
				'type'          => 'model_function',
				'function_name' => 'crudPostsCountColumn',
				'orderable'     => false,
			]);

			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('admin.Active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
				'on_display'    => 'checkbox',
			]);
		}

		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$this->xPanel->addField([
				'name'       => 'name',
				'label'      => trans('admin.Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Name'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);

			$this->xPanel->addField([
				'name'       => 'slug',
				'label'      => trans('admin.Slug'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Will be automatically generated from your name, if left empty'),
				],
				'hint'       => trans('admin.Will be automatically generated from your name, if left empty'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);

			$this->xPanel->addField([
				'name'        => 'parent_id',
				'label'       => trans('admin.Parent'),
				'type'        => 'select2_from_array',
				'options'     => $this->getParentCategoriesOptions(),
				'allows_null' => true,
				'hint'        => trans('admin.blog_category_parent_hint'),
			]);

			$this->xPanel->addField([
				'name'       => 'description',
				'label'      => trans('admin.Description'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Description'),
					'rows'        => 4,
				],
			]);

			$this->xPanel->addField([
				'name'   => 'image_path',
				'label'  => trans('admin.Picture'),
				'type'   => 'image',
				'upload' => true,
				'disk'   => 'public',
			]);

			$this->xPanel->addField([
				'name'  => 'seo_tags',
				'type'  => 'custom_html',
				'value' => '<br><h4 style="margin-bottom: 0;">' . trans('admin.seo_tags') . '</h4>',
			]);

			$this->xPanel->addField([
				'name'  => 'seo_start',
				'type'  => 'custom_html',
				'value' => '<hr style="border: 1px dashed #EFEFEF; margin-top: 0; margin-bottom: 2px;">',
			]);

			$this->xPanel->addField([
				'name'       => 'seo_title',
				'label'      => trans('admin.Title'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Title'),
				],
				'hint'       => trans('admin.seo_title_hint'),
			]);

			$this->xPanel->addField([
				'name'       => 'seo_description',
				'label'      => trans('admin.Description'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Description'),
				],
				'hint'       => trans('admin.seo_description_hint'),
			]);

			$this->xPanel->addField([
				'name'       => 'seo_keywords',
				'label'      => trans('admin.Keywords'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Keywords'),
				],
				'hint'       => trans('admin.comma_separated_hint') . ' ' . trans('admin.seo_keywords_hint'),
			]);

			$this->xPanel->addField([
				'name'  => 'seo_end',
				'type'  => 'custom_html',
				'value' => '<hr style="border: 1px dashed #EFEFEF;">',
			]);

			$defaultActiveValue = $this->onCreatePage ? '1' : '0';
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('admin.Active'),
				'type'    => 'checkbox_switch',
				'default' => $defaultActiveValue,
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

	// PRIVATE

	/**
	 * Get the categories that can be used as parent category
	 *
	 * @return array
	 */
	private function getParentCategoriesOptions(): array
	{
		$entryId = (int)request()->route('category');

		return BlogCategory::query()
			->when($entryId > 0, fn ($query) => $query->where('id', '!=', $entryId))
			->whereNull('parent_id')
			->orderBy('lft')
			->pluck('name', 'id')
			->toArray();
	}

	private function uploadFile(Request $request): Request
	{
		$params = [
			[
				'attribute' => 'image_path',
				'destPath'  => 'app/blog/categories',
				'width'     => (int)config('larapen.media.resize.namedOptions.picture-lg.width', 816),
				'height'    => (int)config('larapen.media.resize.namedOptions.picture-lg.height', 460),
				'quality'   => 100,
			],
		];

		foreach ($params as $param) {
			// Get uploaded image file (should return an UploadedFile object)
			$file = $request->file($param['attribute'], $request->input($param['attribute']));

			// Upload the image & get its local path
			$imagePath = Upload::image($file, $param['destPath'], $param);

			// Set the local path in the input
			$request->merge([$param['attribute'] => $imagePath]);
		}

		return $request;
	}
}
