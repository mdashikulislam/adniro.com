<?php

namespace App\Services\QuickPost;

use App\Models\City;
use App\Models\PostDraft;

/*
 * Puts a draft into the multi-step form's session, exactly in the shape the step 1 form stores
 * (adniro:ai). Validation, guest registration, packages, payment and moderation then run through
 * the one existing path. The highlight marks go in their own session key.
 */

class DraftToPostInput
{
	public const MARKS_KEY = 'quickPostMarks';
	
	/** Values the person may already have typed in the form, kept as they are. */
	private const KEPT_KEYS = ['contact_name', 'auth_field', 'email', 'phone', 'phone_country', 'phone_hidden', 'accept_terms', 'accept_marketing_offers', 'auto_registration'];
	
	public function apply(PostDraft $draft): void
	{
		$data = (array)$draft->draft;
		$values = (array)($data['values'] ?? []);
		
		$input = [
			'country_code' => $draft->country_code ?: config('country.code'),
		];
		
		foreach (['category_id', 'category_type', 'post_type_id', 'title', 'price', 'tags'] as $key) {
			if (array_key_exists($key, $values)) {
				$input[$key] = $values[$key];
			}
		}
		
		if (!empty($values['description'])) {
			$input['description'] = self::descriptionForForm((string)$values['description']);
		}
		
		if (!empty($data['cf'])) {
			$input['cf'] = $data['cf'];
		}
		
		$city = !empty($data['city_id']) ? City::query()->find($data['city_id']) : null;
		if ($city) {
			$input = array_merge($input, self::cityInput($city));
		}
		
		$previous = (array)session('postInput', []);
		foreach (self::KEPT_KEYS as $key) {
			if (array_key_exists($key, $previous)) {
				$input[$key] = $previous[$key];
			}
		}
		
		$marks = (array)($data['marks'] ?? []);
		if (!$city) {
			$marks['city_id'] = 'missing';
		}
		
		session()->put('postInput', $input);
		session()->put(self::MARKS_KEY, [
			'draft'          => $draft->token,
			'fields'         => $marks,
			'policy_flags'   => (array)($data['policy_flags'] ?? []),
			'items_detected' => (int)($data['items_detected'] ?? 1),
		]);
	}
	
	/**
	 * The fallback when the AI cannot help: the person's text goes into the description.
	 */
	public function applyText(string $text): void
	{
		$input = (array)session('postInput', []);
		
		// After an earlier AI draft, its title, category and fields would not match this text:
		// keep only what the person typed themselves
		if (session()->has(self::MARKS_KEY)) {
			$input = array_intersect_key($input, array_flip(self::KEPT_KEYS));
		}
		
		$input['country_code'] ??= config('country.code');
		$input['description'] = self::descriptionForForm(DraftSanitizer::clean($text, (int)config('settings.listing_form.description_max_length', 6000)));
		
		session()->put('postInput', $input);
		session()->forget(self::MARKS_KEY);
	}
	
	/**
	 * The description field is a rich text editor unless the admin switched it off.
	 */
	public static function descriptionForForm(string $text): string
	{
		if (in_array(config('settings.listing_form.wysiwyg_editor'), ['none', '', null], true)) {
			return $text;
		}
		
		$paragraphs = array_filter(array_map('trim', preg_split("/\n{2,}/u", $text) ?: []));
		
		return implode('', array_map(fn ($p) => '<p>' . implode('<br>', array_map('e', explode("\n", $p))) . '</p>', $paragraphs));
	}
	
	/**
	 * The same city keys the step 1 request adds (see PostRequest::prepareForValidation()).
	 */
	public static function cityInput(City $city): array
	{
		$adminType = config('country.admin_type', 0);
		$relAdminType = in_array($adminType, ['1', '2']) ? $adminType : 1;
		$adminCode = data_get($city, 'subadmin' . $relAdminType . '_code', 0);
		$adminCode = data_get($city, 'subAdmin' . $relAdminType . '.code', $adminCode);
		$adminName = data_get($city, 'subAdmin' . $relAdminType . '.name');
		$cityName = (string)data_get($city, 'name');
		
		return [
			'city_id'    => (int)$city->id,
			'admin_type' => $adminType,
			'admin_code' => $adminCode,
			'city_name'  => !empty($adminName) ? $cityName . ', ' . $adminName : $cityName,
		];
	}
}
