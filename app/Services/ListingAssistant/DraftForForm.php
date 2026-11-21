<?php

namespace App\Services\ListingAssistant;

use App\Models\Category;
use App\Models\City;
use App\Models\Field;
use App\Models\PostDraft;

/*
 * A draft as the listing form's AI box fills it in (adniro:ai): the form's own field names and
 * values, plus what the category and location pickers need to select an entry the way a person
 * would. Ported from classifedz.com's in-form box.
 */

final class DraftForForm
{
	private const PATH_SEPARATOR = ' › ';
	private const BULLET = '/^\s*[-•*]\s+(.+)$/u';

	/**
	 * @return array{category: ?array, post_type_id: ?int, title: string, html: string, text: string, price: int|float|null, tags: string[], cf: array, cf_labels: string[], city: ?array, city_name: ?string, marks: array}
	 */
	public function present(PostDraft $draft): array
	{
		$data = (array)$draft->draft;
		$values = (array)($data['values'] ?? []);
		$text = (string)($values['description'] ?? '');
		$cf = self::cfForForm((array)($data['cf'] ?? []));
		$city = !empty($data['city_id']) ? City::query()->find($data['city_id']) : null;
		$location = trim((string)($data['location'] ?? ''));

		return [
			'category'     => $this->category((int)($values['category_id'] ?? 0)),
			'post_type_id' => isset($values['post_type_id']) ? (int)$values['post_type_id'] : null,
			'title'        => (string)($values['title'] ?? ''),
			'html'         => self::paragraphs($text),
			'text'         => $text,
			'price'        => $values['price'] ?? null,
			'tags'         => self::tags($values['tags'] ?? null),
			'cf'           => $cf,
			'cf_labels'    => self::fieldLabels(array_keys($cf)),
			'city'         => $city ? self::city($city) : null,
			'city_name'    => (!$city && $location !== '') ? $location : null,
			'marks'        => (array)($data['marks'] ?? []),
		];
	}

	/**
	 * What the location picker needs: the same keys the step 1 request adds
	 * (see PostRequest::prepareForValidation()).
	 *
	 * @return array{id: int, name: string, admin_type: mixed, admin_code: mixed}
	 */
	public static function city(City $city): array
	{
		$adminType = config('country.admin_type', 0);
		$relAdminType = in_array($adminType, ['1', '2']) ? $adminType : 1;
		$adminCode = data_get($city, 'subadmin' . $relAdminType . '_code', 0);
		$adminCode = data_get($city, 'subAdmin' . $relAdminType . '.code', $adminCode);

		return [
			'id'         => (int)$city->id,
			'name'       => (string)data_get($city, 'name'),
			'admin_type' => $adminType,
			'admin_code' => $adminCode,
		];
	}

	/** Plain text as escaped paragraphs, for the rich text editor. */
	public static function paragraphs(string $text): string
	{
		$blocks = array_filter(array_map('trim', preg_split("/\n{2,}/u", $text) ?: []));

		return implode('', array_map([self::class, 'block'], $blocks));
	}

	/** One block of text: its lines as a paragraph, and runs of "- ", "• " or "* " lines as a list. */
	private static function block(string $block): string
	{
		$html = '';
		$lines = [];
		$items = [];
		foreach (explode("\n", $block) as $line) {
			if (preg_match(self::BULLET, $line, $match)) {
				$html .= self::paragraph($lines);
				$lines = [];
				$items[] = '<li>' . e(trim($match[1])) . '</li>';
				continue;
			}
			$html .= self::bulletList($items);
			$items = [];
			$lines[] = e(trim($line));
		}

		return $html . self::paragraph($lines) . self::bulletList($items);
	}

	private static function paragraph(array $lines): string
	{
		return $lines ? '<p>' . implode('<br>', $lines) . '</p>' : '';
	}

	private static function bulletList(array $items): string
	{
		return $items ? '<ul>' . implode('', $items) . '</ul>' : '';
	}

	/** @return array{id: int, name: string, parent_id: ?int, type: string, path: string}|null */
	private function category(int $id): ?array
	{
		$category = $id > 0 ? Category::query()->withoutGlobalScopes()->with('parent')->find($id) : null;
		if (!$category) {
			return null;
		}

		$name = (string)$category->name;
		$parentName = (string)($category->parent?->name ?? '');

		return [
			'id'        => (int)$category->id,
			'name'      => $name,
			'parent_id' => $category->parent_id ? (int)$category->parent_id : null,
			'type'      => (string)($category->type ?: 'classified'),
			'path'      => $parentName !== '' ? $parentName . self::PATH_SEPARATOR . $name : $name,
		];
	}

	/**
	 * The draft keeps multiple choices as {optionId: optionId} (the form's input shape);
	 * the box ticks each listed option.
	 */
	private static function cfForForm(array $cf): array
	{
		return array_map(fn ($value) => is_array($value) ? array_values(array_map('intval', $value)) : $value, $cf);
	}

	/** @return string[] */
	private static function fieldLabels(array $fieldIds): array
	{
		if (empty($fieldIds)) {
			return [];
		}

		$names = Field::query()->withoutGlobalScopes()->whereIn('id', $fieldIds)->get()->keyBy('id');

		return array_values(array_filter(array_map(fn ($id) => (string)($names->get($id)?->name ?? ''), $fieldIds)));
	}

	/** @return string[] */
	private static function tags(mixed $tags): array
	{
		$list = is_array($tags) ? $tags : explode(',', (string)$tags);

		return array_values(array_filter(array_map(fn ($tag) => trim((string)$tag), $list), fn ($tag) => $tag !== ''));
	}
}
