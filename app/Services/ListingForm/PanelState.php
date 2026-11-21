<?php

namespace App\Services\ListingForm;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\City;

/*
 * adniro: what the listing form's side panel (listing quality and the live buyer preview) needs on a page
 * without the details fields: the multi-step Photos step. Built from the step-1 input kept in the session
 * (create) or from the saved listing (edit), in the shape listing-form/fields.js gives on the details step.
 */

final class PanelState
{
	private const NOT_SALABLE = 'not-salable'; // categories without a price (d.modal.category.js)
	private const HIDDEN_FIELD_TYPES = ['file', 'video'];
	private const OPTION_FIELD_TYPES = ['select', 'radio'];

	/**
	 * Multi-step create: the details the seller entered in step 1 (session 'postInput').
	 *
	 * @return array{state: array, details: array, tags: string[], backUrl: string, config: array}
	 */
	public function fromInput(array $input): array
	{
		$categoryId = (int)($input['category_id'] ?? 0);
		$fields = $categoryId > 0 ? CategoryField::getFields($categoryId) : collect();
		$details = $fields->map(fn ($field) => $this->detail($field, data_get($input, 'cf.' . $field->id)))->filter()->values()->all();

		return $this->build($input, $details, url('posts/create'));
	}

	/**
	 * Multi-step edit: the saved listing.
	 *
	 * @return array{state: array, details: array, tags: string[], backUrl: string, config: array}
	 */
	public function fromPost(array $post): array
	{
		$categoryId = (int)($post['category_id'] ?? 0);
		$fields = $categoryId > 0 ? CategoryField::getFields($categoryId, $post['id'] ?? null) : collect();
		$details = $fields->map(fn ($field) => $this->detail($field, $field->default_value ?? null))->filter()->values()->all();

		return $this->build($post, $details, urlGen()->editPost($post));
	}

	private function build(array $values, array $details, string $backUrl): array
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
			'details' => $details,
			'tags'    => self::tags($values['tags'] ?? []),
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

	/**
	 * One custom field as the listing page shows it ("Additional Details"), or null when it has no value.
	 *
	 * @return array{label: string, values: string[], many: bool}|null
	 */
	private function detail(object $field, mixed $raw): ?array
	{
		if (in_array($field->type, self::HIDDEN_FIELD_TYPES, true) || $raw === null || $raw === '' || $raw === []) {
			return null;
		}
		$options = collect($field->options ?? []);
		$optionValue = fn ($option) => self::translated(is_object($option) ? ($option->value ?? '') : data_get($options->firstWhere('id', (int)$option), 'value', ''));

		$values = match (true) {
			in_array($field->type, self::OPTION_FIELD_TYPES, true) => [$optionValue($raw)],
			// Saved listings give option objects; the step-1 input gives option ids
			$field->type === 'checkbox_multiple'                   => collect((array)$raw)->map($optionValue)->all(),
			$field->type === 'checkbox'                            => (string)$raw === '1' ? [t('Yes')] : [],
			default                                                => [trim((string)$raw)],
		};
		$values = array_values(array_filter($values, fn ($value) => $value !== ''));

		return $values ? ['label' => self::translated($field->name ?? ''), 'values' => $values, 'many' => $field->type === 'checkbox_multiple'] : null;
	}

	/** A translatable value (an object or array per language) in the current language, else the default one */
	private static function translated(mixed $value): string
	{
		if (!is_array($value) && !is_object($value)) {
			return trim((string)$value);
		}
		$values = (array)$value;

		return trim((string)($values[app()->getLocale()] ?? $values[config('app.fallback_locale')] ?? reset($values) ?: ''));
	}

	/** The description as plain text, one line per paragraph or line break */
	public static function plainText(string $html): string
	{
		$text = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6])\b[^>]*>#i', "\n", $html) ?? $html;
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$lines = array_filter(array_map('trim', explode("\n", str_replace("\r", '', $text))), fn ($line) => $line !== '');

		return implode("\n", $lines);
	}

	/** @return string[] */
	private static function tags(mixed $tags): array
	{
		$list = is_array($tags) ? $tags : explode(',', (string)$tags);

		return array_values(array_filter(array_map(fn ($tag) => trim((string)$tag), $list), fn ($tag) => $tag !== ''));
	}
}
