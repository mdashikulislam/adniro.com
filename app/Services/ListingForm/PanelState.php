<?php

namespace App\Services\ListingForm;

use App\Models\Category;
use App\Models\City;

/*
 * adniro: what the listing form's side panel (listing quality and the live buyer preview) needs on a page
 * without the details fields: the multi-step Photos step. Built from the step-1 input kept in the session
 * (create) or from the saved listing (edit), in the shape listing-form/fields.js gives on the details step.
 */

final class PanelState
{
	private const NOT_SALABLE = 'not-salable'; // categories without a price (d.modal.category.js)

	/**
	 * Multi-step create: the details the seller entered in step 1 (session 'postInput').
	 *
	 * @return array{state: array, backUrl: string, config: array}
	 */
	public function fromInput(array $input): array
	{
		return $this->build($input, url('posts/create'));
	}

	/**
	 * Multi-step edit: the saved listing.
	 *
	 * @return array{state: array, backUrl: string, config: array}
	 */
	public function fromPost(array $post): array
	{
		return $this->build($post, urlGen()->editPost($post));
	}

	private function build(array $values, string $backUrl): array
	{
		$categoryId = (int)($values['category_id'] ?? 0);
		$category = $categoryId > 0 ? Category::query()->find($categoryId) : null;
		$cityId = (int)($values['city_id'] ?? 0);
		$city = $cityId > 0 ? City::query()->find($cityId) : null;
		$price = $values['price'] ?? '';

		return [
			'state'   => [
				'categoryId'   => $category ? (int)$category->id : 0,
				'categoryName' => $category ? (string)$category->name : '',
				'title'        => trim((string)($values['title'] ?? '')),
				'description'  => self::plainText((string)($values['description'] ?? '')),
				'price'        => ($price === null || $price === '') ? '' : (string)$price,
				'priceShown'   => !$category || $category->type !== self::NOT_SALABLE,
				'negotiable'   => !empty($values['negotiable']),
				'cityId'       => $city ? (string)$city->id : '',
				'cityName'     => $city ? (string)$city->name : '',
				'email'        => trim((string)($values['email'] ?? '')),
				'phone'        => trim((string)($values['phone'] ?? '')),
			],
			'backUrl' => $backUrl,
			'config'  => [
				'titleMin'       => (int)config('settings.listing_form.title_min_length', 2),
				'titleMax'       => (int)config('settings.listing_form.title_max_length', 150),
				'descriptionMin' => (int)config('settings.listing_form.description_min_length', 5),
				'currency'       => (string)config('currency.symbol', ''),
				'strings'        => trans('listing_form'),
			],
		];
	}

	/** The description as plain text, one line per paragraph or line break */
	public static function plainText(string $html): string
	{
		$text = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6])\b[^>]*>#i', "\n", $html) ?? $html;
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$lines = array_filter(array_map('trim', explode("\n", str_replace("\r", '', $text))), fn ($line) => $line !== '');

		return implode("\n", $lines);
	}
}
