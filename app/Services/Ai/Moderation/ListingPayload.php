<?php

namespace App\Services\Ai\Moderation;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\City;
use App\Models\Field;
use App\Models\FieldOption;
use App\Models\Picture;
use App\Models\Post;
use App\Models\PostValue;
use App\Services\Ai\AiImage;
use Throwable;

/*
 * What the moderation model sees of a listing (adniro:ai).
 * Email and phone are never sent. Text is plain, without HTML.
 */

final class ListingPayload
{
	public const MAX_PICTURES = 5;
	public const MAX_DESCRIPTION = 6000;
	
	/**
	 * @return array<string, mixed>
	 */
	public static function fromPost(Post $post): array
	{
		$raw = $post->getAttributes();
		
		$category = Category::query()->with('parent')->find($raw['category_id'] ?? null);
		$categoryPath = $category
			? trim(($category->parent?->name ? $category->parent->name . ' > ' : '') . $category->name)
			: null;
		
		$postType = null;
		try {
			$postType = !empty($raw['post_type_id']) ? PostType::from((int)$raw['post_type_id'])->label() : null;
		} catch (Throwable $e) {
		}
		
		return [
			'title'         => self::plain($raw['title'] ?? ''),
			'description'   => self::plain($raw['description'] ?? '', self::MAX_DESCRIPTION),
			'tags'          => self::plain($raw['tags'] ?? ''),
			'price'         => ($raw['price'] ?? null) === null ? null : (string)(float)$raw['price'],
			'currency'      => $raw['currency_code'] ?? null,
			'negotiable'    => (bool)($raw['negotiable'] ?? false),
			'category'      => $categoryPath,
			'seller_type'   => $postType,
			'city'          => City::query()->find($raw['city_id'] ?? null)?->name,
			'contact_name'  => self::plain($raw['contact_name'] ?? ''),
			'custom_fields' => self::customFields((int)$post->getKey()),
		];
	}
	
	/**
	 * Up to five photos in display order, resized for the model.
	 *
	 * @return array<int, \Laravel\Ai\Files\Base64Image>
	 */
	/** Every photo of the listing, including those past MAX_PICTURES. */
	public static function pictureCount(Post $post): int
	{
		return Picture::query()->withoutGlobalScopes()->where('post_id', $post->getKey())->count();
	}
	
	public static function images(Post $post): array
	{
		$paths = Picture::query()
			->withoutGlobalScopes()
			->where('post_id', $post->getKey())
			->orderBy('position')
			->orderBy('id')
			->limit(self::MAX_PICTURES)
			->toBase() // raw paths: the model's accessor puts a placeholder image in place of a missing file
			->pluck('file_path');
		
		$images = [];
		foreach ($paths as $path) {
			$image = AiImage::fromStorage(self::currentPath((string)$path));
			if ($image) {
				$images[] = $image;
			}
		}
		
		return $images;
	}
	
	/** Old uploads were stored under pictures/, now files/ (as Picture::filePath() does). */
	private static function currentPath(string $path): string
	{
		return str_contains($path, 'pictures/') ? 'files/' . last(explode('pictures/', $path)) : $path;
	}
	
	/**
	 * The text part of the request. The listing is wrapped in tags and JSON-encoded,
	 * so its content cannot pass for instructions or close the tag.
	 */
	public static function prompt(array $listing, ?string $locale, int $imageCount): string
	{
		$json = json_encode($listing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG);
		
		return '<locale>' . e($locale ?: 'en') . "</locale>\n"
			. "<listing>\n" . $json . "\n</listing>\n"
			. ($imageCount > 0
				? 'The ' . $imageCount . ' attached photo(s) belong to this listing, in display order.'
				: 'This listing has no photos.')
			. "\nLabel this listing according to the policy.";
	}
	
	private static function customFields(int $postId): array
	{
		$rows = PostValue::query()->withoutGlobalScopes()->where('post_id', $postId)->get();
		if ($rows->isEmpty()) {
			return [];
		}
		
		$fields = Field::query()->withoutGlobalScopes()->whereIn('id', $rows->pluck('field_id')->unique())->get()->keyBy('id');
		$optionIds = $rows->pluck('option_id')->filter()->merge(
			$rows->filter(fn ($row) => is_numeric($row->value))->pluck('value')
		)->unique();
		$options = FieldOption::query()->withoutGlobalScopes()->whereIn('id', $optionIds)->get()->keyBy('id');
		
		$out = [];
		foreach ($rows as $row) {
			$field = $fields->get($row->field_id);
			if (!$field) {
				continue;
			}
			
			$value = (string)$row->value;
			$isOptionField = in_array($field->type, ['select', 'radio', 'checkbox_multiple'], true);
			if (!empty($row->option_id) && $options->has($row->option_id)) {
				$value = (string)$options->get($row->option_id)->value;
			} else if ($isOptionField && is_numeric($value) && $options->has((int)$value)) {
				$value = (string)$options->get((int)$value)->value;
			}
			
			$value = self::plain($value, 500);
			if ($value === '') {
				continue;
			}
			
			$name = self::plain((string)$field->name, 100);
			$out[$name] = isset($out[$name]) ? $out[$name] . ', ' . $value : $value;
		}
		
		return $out;
	}
	
	private static function plain(?string $text, int $max = 1000): string
	{
		$text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", (string)$text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = trim(preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{3,}/u", "\n\n", $text) ?? '') ?? '');
		
		return mb_substr($text, 0, $max);
	}
}
