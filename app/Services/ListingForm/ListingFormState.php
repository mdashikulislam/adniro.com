<?php

namespace App\Services\ListingForm;

/*
 * The values the listing form starts with (adniro), for all four pages: create and edit, in
 * single-step and multi-step mode. A new listing reads the multi-step session (empty in
 * single-step mode) and the signed-in member; an edit reads the saved listing.
 *
 * These are the stored values only: the form still lets old() input from a failed submit win,
 * exactly as the stock pages did.
 */
final readonly class ListingFormState
{
	private const DEFAULT_CITY_NAME = '--';
	private const ADMIN_TYPES = ['0', '1', '2'];

	private function __construct(
		public bool $isEdit,
		public ?int $postId,
		public int $categoryId,
		public ?string $categoryType,
		public ?int $postTypeId,
		public ?string $title,
		public ?string $description,
		public mixed $price,
		public mixed $negotiable,
		public mixed $countryCode,
		public mixed $adminType,
		public mixed $adminCode,
		public int $cityId,
		public ?string $cityName,
		public mixed $tags,
		public mixed $isPermanent,
		public bool $contactNameIsHidden,
		public ?string $contactName,
		public ?string $authField,
		public ?string $email,
		public ?string $phone,
		public ?string $phoneCountry,
		public mixed $phoneHidden,
		public mixed $acceptTerms,
		public mixed $acceptMarketingOffers,
	) {
	}

	/**
	 * A new listing: the multi-step session ([] in single-step mode) and the signed-in member.
	 *
	 * @param array $postInput
	 * @param object|null $member the signed-in user
	 * @param array|null $defaults see defaults(); null reads them from the app
	 */
	public static function forCreate(array $postInput, ?object $member = null, ?array $defaults = null): self
	{
		$defaults ??= self::defaults();
		$input = fn (string $key, mixed $default = null) => data_get($postInput, $key, $default);

		[$phone, $phoneCountry] = self::createPhone($postInput, $member, $defaults);
		$memberEmail = !empty($member) ? data_get($member, 'email') : null;

		return new self(
			isEdit: false,
			postId: null,
			categoryId: (int)$input('category_id', 0),
			categoryType: self::text($input('category_type')),
			postTypeId: self::intOrNull($input('post_type_id')),
			title: self::text($input('title')),
			description: self::text($input('description')),
			price: $input('price'),
			negotiable: $input('negotiable'),
			countryCode: $input('country_code', !empty($defaults['ip_country_code']) ? $defaults['ip_country_code'] : 0),
			adminType: $input('admin_type', $defaults['admin_type'] ?? 0),
			adminCode: $input('admin_code', 0),
			cityId: (int)$input('city_id', 0),
			cityName: self::text($input('city_name', self::DEFAULT_CITY_NAME)),
			tags: $input('tags'),
			isPermanent: $input('is_permanent'),
			contactNameIsHidden: !empty($member),
			contactName: self::text(!empty($member) ? data_get($member, 'name') : $input('contact_name')),
			authField: self::text($input('auth_field') ?? ($defaults['auth_field'] ?? null)),
			email: self::text($memberEmail ?? $input('email')),
			phone: self::text($phone),
			phoneCountry: self::text($phoneCountry),
			phoneHidden: $input('phone_hidden'),
			acceptTerms: $input('accept_terms'),
			acceptMarketingOffers: $input('accept_marketing_offers'),
		);
	}

	/**
	 * An existing listing (the post array the edit controllers share with the view).
	 *
	 * @param array|object $post
	 * @param array|null $defaults see defaults(); null reads them from the app
	 */
	public static function fromPost(array|object $post, ?array $defaults = null): self
	{
		$defaults ??= self::defaults();
		$get = fn (string $key, mixed $default = null) => data_get($post, $key, $default);

		$adminType = $defaults['admin_type'] ?? 0;
		$adminType = in_array($adminType, self::ADMIN_TYPES) ? $adminType : 0;
		$relAdminType = in_array($adminType, ['1', '2']) ? $adminType : 1;

		$adminCode = $get("city.subadmin{$relAdminType}_code", 0);
		$adminCode = $get("city.subAdmin{$relAdminType}.code", $adminCode);
		$adminName = $get("city.subAdmin{$relAdminType}.name");
		$cityName = $get('city.name');
		$fullCityName = !empty($adminName) ? $cityName . ', ' . $adminName : $cityName;

		return new self(
			isEdit: true,
			postId: self::intOrNull($get('id')),
			categoryId: (int)$get('category.id', 0),
			categoryType: self::text($get('category.type')),
			postTypeId: self::intOrNull($get('post_type_id')),
			title: self::text($get('title')),
			description: self::text($get('description')),
			price: $get('price'),
			negotiable: $get('negotiable'),
			countryCode: $get('country_code') ?? ($defaults['country_code'] ?? null),
			adminType: $adminType,
			adminCode: $adminCode,
			cityId: (int)$get('city.id', 0),
			cityName: self::text($fullCityName),
			tags: $get('tags'),
			isPermanent: $get('is_permanent'),
			contactNameIsHidden: false,
			contactName: self::text($get('contact_name')),
			authField: self::text($get('auth_field') ?? ($defaults['auth_field'] ?? null)),
			email: self::text($get('email')),
			phone: self::text($get('phone')),
			phoneCountry: self::text($get('phone_country') ?? ($defaults['country_code'] ?? null)),
			phoneHidden: $get('phone_hidden'),
			acceptTerms: null,
			acceptMarketingOffers: null,
		);
	}

	/**
	 * The site settings the form falls back to.
	 *
	 * @return array{country_code: mixed, ip_country_code: mixed, admin_type: mixed, auth_field: mixed}
	 */
	public static function defaults(): array
	{
		return [
			'country_code'    => config('country.code'),
			'ip_country_code' => config('ipCountry.code'),
			'admin_type'      => config('country.admin_type', 0),
			'auth_field'      => getAuthField(),
		];
	}

	/**
	 * A member with a full phone number uses it; otherwise the typed one (or the site country).
	 *
	 * @return array{0: mixed, 1: mixed}
	 */
	private static function createPhone(array $postInput, ?object $member, array $defaults): array
	{
		$memberHasPhone = !empty($member)
			&& data_get($member, 'country_code') !== null
			&& !empty(data_get($member, 'phone'))
			&& data_get($member, 'phone_country') !== null;

		if ($memberHasPhone) {
			return [data_get($member, 'phone'), data_get($member, 'phone_country')];
		}

		return [
			data_get($postInput, 'phone'),
			data_get($postInput, 'phone_country', $defaults['country_code'] ?? null),
		];
	}

	private static function text(mixed $value): ?string
	{
		return ($value === null) ? null : (string)$value;
	}

	private static function intOrNull(mixed $value): ?int
	{
		return is_numeric($value) ? (int)$value : null;
	}
}
