<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Http\Controllers\Web\Admin;

use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\BlogPostRequest as StoreRequest;
use App\Http\Requests\Admin\BlogPostRequest as UpdateRequest;
use App\Http\Requests\Admin\Request;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Throwable;

class BlogPostController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(BlogPost::class);
		$this->xPanel->setRoute(urlGen()->adminUri('blog/posts'));
		$this->xPanel->setEntityNameStrings(trans('admin.blog_post'), trans('admin.blog_posts'));
		if (!request()->input('order')) {
			$this->xPanel->orderByDesc('published_at');
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
					'name'  => 'title',
					'type'  => 'text',
					'label' => mb_ucfirst(trans('admin.title')),
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', 'title', 'LIKE', "%$value%");
				}
			);

			$this->xPanel->addFilter(
				options: [
					'name'  => 'category',
					'type'  => 'select2',
					'label' => trans('admin.blog_category'),
				],
				values: $this->getCategoriesOptions(),
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', 'category_id', '=', $value);
				}
			);

			$this->xPanel->addFilter(
				options: [
					'name'  => 'country',
					'type'  => 'select2',
					'label' => mb_ucfirst(trans('admin.country')),
				],
				values: $this->getCountriesOptions(),
				filterLogic: function ($value) {
					if ($value == 'global') {
						$this->xPanel->addClause('whereNull', 'country_code');
					} else {
						$this->xPanel->addClause('where', 'country_code', '=', $value);
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
					1 => trans('admin.Activated'),
					2 => trans('admin.Unactivated'),
					3 => trans('admin.blog_featured'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', 'active', '=', 1);
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
					}
					if ($value == 3) {
						$this->xPanel->addClause('where', 'featured', '=', 1);
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
				'name'          => 'image_path',
				'label'         => trans('admin.Picture'),
				'type'          => 'model_function',
				'function_name' => 'crudImageColumn',
				'orderable'     => false,
			]);

			$this->xPanel->addColumn([
				'name'          => 'title',
				'label'         => mb_ucfirst(trans('admin.title')),
				'type'          => 'model_function',
				'function_name' => 'crudTitleColumn',
			]);

			$this->xPanel->addColumn([
				'name'          => 'category_id',
				'label'         => trans('admin.blog_category'),
				'type'          => 'model_function',
				'function_name' => 'crudCategoryColumn',
			]);

			$this->xPanel->addColumn([
				'name'          => 'country_code',
				'label'         => mb_ucfirst(trans('admin.country')),
				'type'          => 'model_function',
				'function_name' => 'crudCountryTargetColumn',
			]);

			$this->xPanel->addColumn([
				'name'  => 'published_at',
				'label' => trans('admin.blog_published_at'),
				'type'  => 'datetime',
			]);

			$this->xPanel->addColumn([
				'name'  => 'views',
				'label' => trans('admin.blog_views'),
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
				'name'       => 'title',
				'label'      => mb_ucfirst(trans('admin.title')),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => mb_ucfirst(trans('admin.title')),
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
				'name'        => 'category_id',
				'label'       => trans('admin.blog_category'),
				'type'        => 'select2_from_array',
				'options'     => $this->getCategoriesOptions(),
				'allows_null' => false,
				'required'    => true,
				'wrapper'     => [
					'class' => 'col-md-6',
				],
			]);

			$this->xPanel->addField([
				'name'        => 'country_code',
				'label'       => mb_ucfirst(trans('admin.country')),
				'type'        => 'select2_from_array',
				'options'     => getCountries(),
				'allows_null' => true,
				'hint'        => trans('admin.blog_country_hint'),
				'wrapper'     => [
					'class' => 'col-md-6',
				],
			]);

			$this->xPanel->addField([
				'name'   => 'image_path',
				'label'  => trans('admin.blog_cover_image'),
				'type'   => 'image',
				'upload' => true,
				'disk'   => 'public',
				'hint'   => trans('admin.blog_cover_image_hint'),
			]);

			$this->xPanel->addField([
				'name'       => 'excerpt',
				'label'      => trans('admin.blog_excerpt'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.blog_excerpt'),
					'rows'        => 3,
				],
				'hint'       => trans('admin.blog_excerpt_hint'),
			]);

			$wysiwygEditor = config('settings.other.wysiwyg_editor');
			$wysiwygEditorViewPath = '/views/admin/panel/fields/' . $wysiwygEditor . '.blade.php';
			$this->xPanel->addField([
				'name'       => 'content',
				'label'      => trans('admin.Content'),
				'type'       => ($wysiwygEditor != 'none' && file_exists(resource_path() . $wysiwygEditorViewPath))
					? $wysiwygEditor
					: 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Content'),
					'id'          => 'blogPostContent',
					'rows'        => 20,
				],
			]);

			$this->xPanel->addField([
				'name'    => 'published_at',
				'label'   => trans('admin.blog_published_at'),
				'type'    => 'datetime',
				'hint'    => trans('admin.blog_published_at_hint'),
				'wrapper' => [
					'class' => 'col-md-6',
				],
			]);

			$this->xPanel->addField([
				'name'    => 'featured',
				'label'   => trans('admin.blog_featured'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('admin.blog_featured_hint'),
				'wrapper' => [
					'class' => 'col-md-6',
				],
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
	 * Get the blog categories list (as options)
	 *
	 * @return array
	 */
	/**
	 * Get the countries list (as filter options),
	 * with an extra option for the posts displayed in all the countries
	 *
	 * @return array
	 */
	private function getCountriesOptions(): array
	{
		return array_merge(['global' => trans('admin.blog_all_countries')], getCountries());
	}

	private function getCategoriesOptions(): array
	{
		return BlogCategory::query()
			->orderBy('lft')
			->orderBy('name')
			->pluck('name', 'id')
			->toArray();
	}

	private function uploadFile(Request $request): Request
	{
		$params = [
			[
				'attribute' => 'image_path',
				'destPath'  => 'app/blog/posts',
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
