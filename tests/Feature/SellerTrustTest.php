<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SellerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * What the seller card tells buyers (adniro, phase 4): verified by email, and the listings
 * the public can see.
 */

class SellerTrustTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;
	
	public function test_a_seller_with_a_verified_email_is_verified(): void
	{
		// Arrange: the owner chose email-only verification
		$verified = $this->user(7, ['email_verified_at' => now(), 'phone_verified_at' => null]);
		$unverified = $this->user(8, ['email_verified_at' => null, 'phone_verified_at' => now()]);
		
		// Act & Assert
		$this->assertTrue(SellerService::findByUserId($verified->id)['is_verified']);
		$this->assertFalse(SellerService::findByUserId($unverified->id)['is_verified']);
	}
	
	public function test_the_listing_page_marks_a_verified_seller_with_an_icon_after_their_name(): void
	{
		// Arrange
		$this->seedStockData();
		$seller = $this->stockMember(71);
		$post = $this->stockListing('Grey corner sofa', ['user_id' => $seller->id]);
		$icon = '~<span class="seller-name">\s*<a [^>]+>[^<]+</a>\s*<i class="bi bi-patch-check-fill text-primary ms-1 seller-verified" role="img"\s+aria-label="Verified seller"[^>]*></i>\s*</span>~s';

		// Act
		$verified = $this->get(urlGen()->post($post))->assertOk()->getContent();
		DB::table('users')->where('id', $seller->id)->update(['email_verified_at' => null]);
		$unverified = $this->get(urlGen()->post($post) . '?again=1')->assertOk()->getContent();

		// Assert: an icon inside the name, no "Verified seller" line of its own; nothing for an unconfirmed email
		$this->assertMatchesRegularExpression($icon, $verified);
		$this->assertStringNotContainsString('seller-verified badge', $verified);
		$this->assertStringNotContainsString('seller-verified', $unverified);
		// Without a photo the seller's initial stands in for the grey placeholder
		$this->assertMatchesRegularExpression('~<div class="seller-avatar">\s*<span class="seller-avatar-initial" aria-hidden="true">[A-Z]</span>~', $verified);
	}
	
	public function test_only_listings_the_public_can_see_are_counted(): void
	{
		// Arrange
		$seller = $this->user(7, ['email_verified_at' => now()]);
		$this->listing(['title' => 'Live']);
		$this->listing(['title' => 'Also live']);
		$this->listing(['title' => 'Waiting for the AI check', 'moderation_status' => 'pending']);
		$this->listing(['title' => 'Rejected', 'moderation_status' => 'rejected']);
		$this->listing(['title' => 'Archived', 'archived_at' => now()]);
		$this->listing(['title' => 'Email not confirmed', 'email_verified_at' => null]);
		$this->listing(['title' => 'Someone else', 'user_id' => 99]);
		
		// Act
		$count = SellerService::liveListingsCount(SellerService::findByUserId($seller->id));
		
		// Assert
		$this->assertSame(2, $count);
	}
	
	public function test_listings_waiting_for_human_review_are_not_counted_when_review_is_on(): void
	{
		// Arrange
		config(['settings.listing_form.listings_review_activation' => '1']);
		$seller = $this->user(7, ['email_verified_at' => now()]);
		$this->listing(['title' => 'Reviewed']);
		$this->listing(['title' => 'Not reviewed yet', 'reviewed_at' => null]);
		
		// Act & Assert
		$this->assertSame(1, SellerService::liveListingsCount(SellerService::findByUserId($seller->id)));
	}
	
	private function user(int $id, array $attributes = []): User
	{
		return User::query()->forceCreate(array_merge([
			'id' => $id, 'name' => "Seller {$id}", 'username' => "seller{$id}", 'email' => "seller{$id}@example.test",
			'auth_field' => 'email', 'password' => bcrypt(str()->random(32)),
		], $attributes));
	}
	
	private function listing(array $overrides = []): void
	{
		DB::table('posts')->insert(array_merge([
			'country_code' => 'US', 'user_id' => 7, 'category_id' => 1, 'city_id' => 1, 'post_type_id' => 1,
			'title' => 'Sofa', 'description' => 'A sofa.', 'price' => 10, 'currency_code' => 'USD',
			'contact_name' => 'Sam', 'email' => 'seller7@example.test', 'auth_field' => 'email',
			'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(),
			'moderation_status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
		], $overrides));
	}
}
