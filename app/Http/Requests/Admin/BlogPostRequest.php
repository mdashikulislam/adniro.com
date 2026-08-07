<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Http\Requests\Admin;

class BlogPostRequest extends Request
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
			'title'           => ['required', 'min:2', 'max:191'],
			'slug'            => ['nullable', 'max:191', 'unique:blog_posts,slug' . (!empty($id) ? ',' . $id : '')],
			'category_id'     => ['required', 'integer', 'exists:blog_categories,id'],
			'country_code'    => ['nullable', 'string', 'size:2'],
			'excerpt'         => ['nullable', 'max:1000'],
			'content'         => ['required', 'max:16000000'],
			'published_at'    => ['nullable', 'date'],
			'seo_title'       => ['nullable', 'max:191'],
			'seo_description' => ['nullable', 'max:500'],
			'seo_keywords'    => ['nullable', 'max:500'],
		];
	}
}
