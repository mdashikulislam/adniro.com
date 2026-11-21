<?php

namespace Tests\Unit\ListingForm;

use App\Services\ListingForm\ListingFormState;
use PHPUnit\Framework\TestCase;

class ListingFormStateTest extends TestCase
{
	private const DEFAULTS = [
		'country_code'    => 'US',
		'ip_country_code' => 'CA',
		'admin_type'      => '1',
		'auth_field'      => 'email',
	];

	public function test_a_new_listing_starts_empty_with_the_site_defaults(): void
	{
		// Act
		$form = ListingFormState::forCreate([], null, self::DEFAULTS);

		// Assert
		$this->assertFalse($form->isEdit);
		$this->assertNull($form->postId);
		$this->assertSame(0, $form->categoryId);
		$this->assertNull($form->title);
		$this->assertSame('CA', $form->countryCode);
		$this->assertSame('1', $form->adminType);
		$this->assertSame(0, $form->adminCode);
		$this->assertSame(0, $form->cityId);
		$this->assertSame('--', $form->cityName);
		$this->assertSame('email', $form->authField);
		$this->assertSame('US', $form->phoneCountry);
		$this->assertFalse($form->contactNameIsHidden);
	}

	public function test_a_new_listing_falls_back_to_zero_when_the_visitor_country_is_unknown(): void
	{
		// Act
		$form = ListingFormState::forCreate([], null, ['ip_country_code' => null] + self::DEFAULTS);

		// Assert
		$this->assertSame(0, $form->countryCode);
	}

	public function test_the_multi_step_session_fills_a_new_listing(): void
	{
		// Arrange
		$postInput = [
			'category_id' => 12, 'category_type' => 'classified', 'post_type_id' => 2, 'title' => 'Grey sofa',
			'description' => 'Comfy', 'price' => '150', 'negotiable' => '1', 'country_code' => 'US', 'admin_type' => '2',
			'admin_code' => 'US.IL', 'city_id' => '77', 'city_name' => 'Springfield', 'tags' => ['sofa'],
			'is_permanent' => '1', 'contact_name' => 'Sam', 'auth_field' => 'phone', 'email' => 'sam@example.test',
			'phone' => '+15550100', 'phone_country' => 'CA', 'phone_hidden' => '1', 'accept_terms' => '1',
			'accept_marketing_offers' => '0',
		];

		// Act
		$form = ListingFormState::forCreate($postInput, null, self::DEFAULTS);

		// Assert
		$this->assertSame(12, $form->categoryId);
		$this->assertSame('classified', $form->categoryType);
		$this->assertSame(2, $form->postTypeId);
		$this->assertSame('Grey sofa', $form->title);
		$this->assertSame('Comfy', $form->description);
		$this->assertSame('150', $form->price);
		$this->assertSame('1', $form->negotiable);
		$this->assertSame('US', $form->countryCode);
		$this->assertSame('2', $form->adminType);
		$this->assertSame('US.IL', $form->adminCode);
		$this->assertSame(77, $form->cityId);
		$this->assertSame('Springfield', $form->cityName);
		$this->assertSame(['sofa'], $form->tags);
		$this->assertSame('1', $form->isPermanent);
		$this->assertSame('Sam', $form->contactName);
		$this->assertSame('phone', $form->authField);
		$this->assertSame('sam@example.test', $form->email);
		$this->assertSame('+15550100', $form->phone);
		$this->assertSame('CA', $form->phoneCountry);
		$this->assertSame('1', $form->phoneHidden);
		$this->assertSame('1', $form->acceptTerms);
		$this->assertSame('0', $form->acceptMarketingOffers);
	}

	public function test_a_signed_in_member_uses_their_own_contact_details(): void
	{
		// Arrange
		$member = (object)[
			'name' => 'Member 50', 'email' => 'member50@example.test', 'phone' => '+15550199',
			'phone_country' => 'US', 'country_code' => 'US',
		];
		$postInput = ['contact_name' => 'Old', 'email' => 'old@example.test', 'phone' => '+15550000', 'phone_country' => 'CA'];

		// Act
		$form = ListingFormState::forCreate($postInput, $member, self::DEFAULTS);

		// Assert
		$this->assertTrue($form->contactNameIsHidden);
		$this->assertSame('Member 50', $form->contactName);
		$this->assertSame('member50@example.test', $form->email);
		$this->assertSame('+15550199', $form->phone);
		$this->assertSame('US', $form->phoneCountry);
	}

	public function test_a_member_without_a_phone_keeps_the_typed_phone(): void
	{
		// Arrange
		$member = (object)['name' => 'Member 50', 'email' => 'member50@example.test', 'phone' => null, 'phone_country' => null, 'country_code' => 'US'];

		// Act
		$form = ListingFormState::forCreate(['phone' => '+15550000', 'phone_country' => 'CA'], $member, self::DEFAULTS);

		// Assert
		$this->assertSame('+15550000', $form->phone);
		$this->assertSame('CA', $form->phoneCountry);
	}

	public function test_an_edit_reads_the_saved_listing(): void
	{
		// Arrange
		$post = [
			'id' => 9, 'category' => ['id' => 12, 'type' => 'classified'], 'post_type_id' => 1, 'title' => 'Grey sofa',
			'description' => 'Comfy', 'price' => '150.00', 'negotiable' => 0, 'country_code' => 'US', 'tags' => ['sofa'],
			'is_permanent' => 0, 'contact_name' => 'Sam', 'auth_field' => 'phone', 'email' => 'sam@example.test',
			'phone' => '+15550100', 'phone_country' => 'US', 'phone_hidden' => 1,
			'city' => ['id' => 77, 'name' => 'Springfield', 'subadmin1_code' => 'US.IL', 'subAdmin1' => ['code' => 'US.IL', 'name' => 'Illinois']],
		];

		// Act
		$form = ListingFormState::fromPost($post, self::DEFAULTS);

		// Assert
		$this->assertTrue($form->isEdit);
		$this->assertSame(9, $form->postId);
		$this->assertSame(12, $form->categoryId);
		$this->assertSame('classified', $form->categoryType);
		$this->assertSame('Grey sofa', $form->title);
		$this->assertSame('US', $form->countryCode);
		$this->assertSame('1', $form->adminType);
		$this->assertSame('US.IL', $form->adminCode);
		$this->assertSame(77, $form->cityId);
		$this->assertSame('Springfield, Illinois', $form->cityName);
		$this->assertSame('phone', $form->authField);
		$this->assertSame('US', $form->phoneCountry);
		$this->assertSame(1, $form->phoneHidden);
		$this->assertFalse($form->contactNameIsHidden);
	}

	public function test_an_edit_falls_back_to_the_site_country_and_first_admin_level(): void
	{
		// Arrange
		$post = ['id' => 9, 'city' => ['id' => 77, 'name' => 'Springfield', 'subadmin1_code' => 'US.IL']];

		// Act
		$form = ListingFormState::fromPost($post, ['admin_type' => '0', 'auth_field' => 'email'] + self::DEFAULTS);

		// Assert
		$this->assertSame('US', $form->countryCode);
		$this->assertSame('0', $form->adminType);
		$this->assertSame('US.IL', $form->adminCode);
		$this->assertSame('Springfield', $form->cityName);
		$this->assertSame('email', $form->authField);
		$this->assertSame('US', $form->phoneCountry);
	}

	public function test_an_unknown_admin_type_is_treated_as_none(): void
	{
		// Act
		$form = ListingFormState::fromPost(['id' => 9], ['admin_type' => '7'] + self::DEFAULTS);

		// Assert
		$this->assertSame(0, $form->adminType);
		$this->assertSame(0, $form->adminCode);
		$this->assertSame(0, $form->cityId);
	}

	public static function statuses(): array
	{
		return [
			'approved and reviewed'   => [['moderation_status' => 'approved', 'reviewed_at' => '2026-11-01'], 'live'],
			'old listing, no status'  => [['reviewed_at' => '2026-11-01'], 'live'],
			'not reviewed yet'        => [['moderation_status' => 'approved', 'reviewed_at' => null], 'pending'],
			'held by moderation'      => [['moderation_status' => 'needs_review', 'reviewed_at' => '2026-11-01'], 'review'],
			'waiting for moderation'  => [['moderation_status' => 'pending', 'reviewed_at' => '2026-11-01'], 'review'],
			'rejected'                => [['moderation_status' => 'rejected', 'reviewed_at' => '2026-11-01'], 'rejected'],
			'archived'                => [['moderation_status' => 'approved', 'reviewed_at' => '2026-11-01', 'archived_at' => '2026-11-02'], 'archived'],
		];
	}
	
	#[\PHPUnit\Framework\Attributes\DataProvider('statuses')]
	public function test_an_edit_knows_whether_the_listing_is_live(array $post, string $status): void
	{
		// Act
		$form = ListingFormState::fromPost(['id' => 9] + $post, self::DEFAULTS);
		
		// Assert
		$this->assertSame($status, $form->status);
	}
	
	public function test_a_new_listing_has_no_status(): void
	{
		// Act
		$form = ListingFormState::forCreate([], null, self::DEFAULTS);
		
		// Assert
		$this->assertNull($form->status);
	}
}
