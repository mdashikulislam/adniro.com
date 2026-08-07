<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Http\Requests\Admin;

class BlogCategoryRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$id = $this->input('id');

		return [
			'name'            => ['required', 'min:2', 'max:191'],
			'slug'            => ['nullable', 'max:191', 'unique:blog_categories,slug' . (!empty($id) ? ',' . $id : '')],
			'description'     => ['nullable', 'max:65000'],
			'seo_title'       => ['nullable', 'max:191'],
			'seo_description' => ['nullable', 'max:500'],
			'seo_keywords'    => ['nullable', 'max:500'],
		];
	}
}
