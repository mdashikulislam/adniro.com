<?php

namespace App\Services\QuickPost;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Field;
use App\Services\Ai\Moderation\Policy;
use Carbon\Carbon;
use Throwable;

/*
 * The model's draft is untrusted: everything is checked against the catalog and the site's rules
 * (adniro:ai). Missing required fields are computed here, never taken from the model.
 *
 * Marks tell the form which fields to highlight: "check" for a low-confidence or adjusted value,
 * "missing" for a required value the draft could not fill.
 */

class DraftSanitizer
{
	public function __construct(private readonly CatalogBuilder $catalog)
	{
	}
	
	/**
	 * @return array{listable: bool, unusable_reason: ?string, values: array, cf: array, marks: array, policy_flags: array, items_detected: int, location: string}
	 */
	public function sanitize(array $raw, ?string $siteCurrency = null): array
	{
		$marks = [];
		
		$listable = (bool)($raw['is_listable'] ?? false);
		$unusableReason = self::clean((string)($raw['unusable_reason'] ?? ''), 300) ?: null;
		
		// Title and description
		$titleMax = (int)config('settings.listing_form.title_max_length', 150);
		$title = self::clean((string)data_get($raw, 'title.value', ''), min($titleMax, 150), true);
		$description = self::clean((string)data_get($raw, 'description.value', ''), (int)config('settings.listing_form.description_max_length', 6000));
		$this->markByConfidence($marks, 'title', $title, data_get($raw, 'title.confidence'));
		$this->markByConfidence($marks, 'description', $description, data_get($raw, 'description.confidence'));
		
		// Category: the chosen one, else an alternative, else none
		$map = $this->catalog->fieldsByCategory();
		$categoryId = null;
		$candidates = array_merge([data_get($raw, 'category.id')], (array)data_get($raw, 'category.alternatives', []));
		foreach ($candidates as $index => $candidate) {
			if (is_numeric($candidate) && array_key_exists((int)$candidate, $map)) {
				$categoryId = (int)$candidate;
				if ($index > 0) {
					$marks['category_id'] = 'check';
				}
				break;
			}
		}
		if (is_null($categoryId)) {
			$marks['category_id'] = 'missing';
		} else if (!isset($marks['category_id'])) {
			$this->markByConfidence($marks, 'category_id', $categoryId, data_get($raw, 'category.confidence'));
		}
		$category = $categoryId ? Category::query()->withoutGlobalScopes()->find($categoryId) : null;
		
		// Seller type, only when the form shows it
		$postTypeId = null;
		if (config('settings.listing_form.show_listing_type') == '1') {
			$candidate = (int)($raw['post_type_id'] ?? 0);
			if (in_array($candidate, PostType::values(), true)) {
				$postTypeId = $candidate;
				$marks['post_type_id'] = 'check';
			} else {
				$marks['post_type_id'] = 'missing';
			}
		}
		
		// Price: only a price the person stated
		$price = null;
		if (data_get($raw, 'price.stated') === true && is_numeric(data_get($raw, 'price.amount')) && (float)data_get($raw, 'price.amount') > 0) {
			$price = round((float)data_get($raw, 'price.amount'), 2);
			$currency = strtoupper(trim((string)data_get($raw, 'price.currency', '')));
			if ($currency !== '' && !empty($siteCurrency) && $currency !== strtoupper($siteCurrency)) {
				$marks['price'] = 'check';
			}
		} else if (config('settings.listing_form.price_mandatory') == '1' && ($category?->type ?? '') !== 'not-salable') {
			$marks['price'] = 'missing';
		}
		
		// Tags, through the site's own cleaner and limit
		$tags = array_values(array_filter(array_map(
			fn ($tag) => self::clean(ltrim((string)$tag, '#'), 30, true),
			array_slice((array)($raw['tags'] ?? []), 0, 10)
		)));
		$tags = empty($tags) ? null : tagCleaner($tags);
		
		// Custom fields of the chosen category
		$cf = $categoryId ? $this->customFields((array)($raw['custom_fields'] ?? []), $map[$categoryId] ?? [], $marks) : [];
		
		$policyFlags = array_values(array_unique(array_filter(
			(array)($raw['policy_flags'] ?? []),
			fn ($code) => is_string($code) && Policy::severity($code) !== null
		)));
		
		if ($title === '') {
			$marks['title'] = 'missing';
		}
		if ($description === '') {
			$marks['description'] = 'missing';
		}
		
		return [
			'listable'        => $listable,
			'unusable_reason' => $unusableReason,
			'values'          => array_filter([
				'category_id'   => $categoryId,
				'category_type' => $category?->type,
				'post_type_id'  => $postTypeId,
				'title'         => $title,
				'description'   => $description,
				'price'         => $price,
				'tags'          => $tags,
			], fn ($value) => !is_null($value) && $value !== ''),
			'cf'              => $cf,
			'marks'           => $marks,
			'policy_flags'    => $policyFlags,
			'items_detected'  => max(0, (int)($raw['items_detected'] ?? 1)),
			'location'        => self::clean((string)($raw['location'] ?? ''), 120, true),
		];
	}
	
	/**
	 * Custom field values in the form's own shape: cf[fieldId] = value or option id,
	 * cf[fieldId][optionId] = optionId for multiple checkboxes.
	 */
	private function customFields(array $rawFields, array $allowedIds, array &$marks): array
	{
		$fields = Field::query()->withoutGlobalScopes()->with('options')->whereIn('id', $allowedIds)->get()->keyBy('id');
		$cf = [];
		
		foreach ($rawFields as $item) {
			$fieldId = (int)data_get($item, 'field_id');
			$field = $fields->get($fieldId);
			if (!$field || array_key_exists($fieldId, $cf)) {
				continue;
			}
			
			$optionIds = $field->options->pluck('id')->map(fn ($id) => (int)$id)->all();
			$given = array_values(array_filter(array_map('intval', (array)data_get($item, 'option_ids', [])), fn ($id) => in_array($id, $optionIds, true)));
			$text = trim((string)data_get($item, 'value', ''));
			
			// A select answered with the option's text instead of its id
			if (empty($given) && $text !== '' && in_array($field->type, ['select', 'radio', 'checkbox_multiple'], true)) {
				foreach ($field->options as $option) {
					if (mb_strtolower(trim((string)$option->value)) === mb_strtolower($text)) {
						$given = [(int)$option->id];
						break;
					}
				}
			}
			
			$value = match ($field->type) {
				'select', 'radio'   => $given[0] ?? null,
				'checkbox_multiple' => empty($given) ? null : array_combine($given, $given),
				'checkbox'          => in_array(mb_strtolower($text), ['1', 'true', 'yes', 'on'], true) ? 1 : null,
				'number'            => $this->wholeNumber($text),
				'date', 'date_time' => $this->date($text),
				'text', 'textarea'  => self::clean($text, $field->type === 'text' ? 200 : 1000, $field->type === 'text') ?: null,
				default             => null, // url and anything else: never filled by the AI
			};
			
			if (is_null($value)) {
				continue;
			}
			
			$cf[$fieldId] = $value;
			if (data_get($item, 'confidence') !== 'high') {
				$marks['cf.' . $fieldId] = 'check';
			}
		}
		
		foreach ($fields as $field) {
			if ($field->required && !array_key_exists((int)$field->id, $cf)) {
				$marks['cf.' . $field->id] = 'missing';
			}
		}
		
		return $cf;
	}
	
	/**
	 * "128", "128 GB" or "92,000 km" give 128 and 92000. Decimals are not whole numbers.
	 */
	private function wholeNumber(string $text): ?string
	{
		if (!preg_match('/^\s*(-?\d[\d\s,]*)(?![\d.])/u', $text, $match)) {
			return null;
		}
		
		$digits = preg_replace('/[^\d-]/', '', $match[1]);
		
		return ($digits === '' || $digits === '-') ? null : (string)(int)$digits;
	}
	
	private function date(string $text): ?string
	{
		if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $text)) {
			return null;
		}
		
		try {
			return Carbon::parse(substr($text, 0, 10))->format('Y-m-d');
		} catch (Throwable $e) {
			return null;
		}
	}
	
	private function markByConfidence(array &$marks, string $key, $value, $confidence): void
	{
		if ($value !== null && $value !== '' && $confidence !== 'high') {
			$marks[$key] = 'check';
		}
	}
	
	/**
	 * Plain text without tags, links, emails or phone numbers, cut to a length.
	 */
	public static function clean(string $text, int $max, bool $singleLine = false): string
	{
		$text = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$text = preg_replace('~\b(?:https?://|www\.)\S+~iu', '', $text) ?? '';
		$text = preg_replace('~[\w.+-]+@[\w-]+(?:\.[\w-]+)+~u', '', $text) ?? '';
		$text = preg_replace('~\+?\d[\d\s().-]{7,}\d~u', '', $text) ?? '';
		$text = $singleLine
			? preg_replace('/\s+/u', ' ', $text)
			: preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{3,}/u", "\n\n", str_replace("\r", '', $text)) ?? '');
		$text = trim((string)$text);
		
		return mb_substr($text, 0, max(0, $max));
	}
}
