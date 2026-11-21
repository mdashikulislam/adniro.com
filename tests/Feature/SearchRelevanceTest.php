<?php

namespace Tests\Feature;

use App\Helpers\Services\Search\PostQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Keyword search puts the best matches first (adniro, phase 6): "phone" finds phones, not every
 * listing that mentions a phone number.
 */

class SearchRelevanceTest extends TestCase
{
	use RefreshDatabase;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		config(['country.code' => 'US', 'settings.listing_form.listings_review_activation' => '0']);
		DB::table('categories')->insert([
			['id' => 1, 'parent_id' => null, 'name' => json_encode(['en' => 'Phones & Tablets']), 'slug' => 'phones', 'lft' => 1, 'rgt' => 4, 'depth' => 0, 'type' => 'classified', 'active' => 1],
			['id' => 2, 'parent_id' => 1, 'name' => json_encode(['en' => 'Mobile phones']), 'slug' => 'mobile-phones', 'lft' => 2, 'rgt' => 3, 'depth' => 1, 'type' => 'classified', 'active' => 1],
			['id' => 3, 'parent_id' => null, 'name' => json_encode(['en' => 'Cars']), 'slug' => 'cars', 'lft' => 5, 'rgt' => 6, 'depth' => 0, 'type' => 'classified', 'active' => 1],
		]);
		DB::table('cities')->insert(['id' => 1, 'country_code' => 'US', 'name' => json_encode(['en' => 'Springfield']), 'latitude' => 0, 'longitude' => 0, 'active' => 1]);
	}
	
	public function test_a_phone_listing_comes_before_a_newer_listing_that_mentions_a_phone(): void
	{
		// Arrange
		$this->listing(1, 'iPhone 13, 128 GB, blue', 'Like new, with box.', 2, now()->subDays(5));
		$this->listing(2, 'Toyota Corolla 2018', 'Call my phone after 6pm.', 3, now()->subHour());
		
		// Act
		$titles = $this->search('phone');
		
		// Assert
		$this->assertSame(['IPhone 13, 128 GB, blue', 'Toyota Corolla 2018'], $titles);
	}
	
	public function test_without_a_keyword_the_newest_listings_come_first(): void
	{
		// Arrange
		$this->listing(1, 'Older sofa', 'Grey.', 3, now()->subDays(5));
		$this->listing(2, 'Newer sofa', 'Blue.', 3, now()->subHour());
		
		// Act & Assert
		$this->assertSame(['Newer sofa', 'Older sofa'], $this->search(null));
	}
	
	public function test_the_visitor_can_still_sort_by_date(): void
	{
		// Arrange
		$this->listing(1, 'iPhone 13, 128 GB, blue', 'Like new.', 2, now()->subDays(5));
		$this->listing(2, 'Toyota Corolla 2018', 'Call my phone.', 3, now()->subHour());
		
		// Act & Assert
		$this->assertSame(['Toyota Corolla 2018', 'IPhone 13, 128 GB, blue'], $this->search('phone', 'date'));
	}
	
	/** @return string[] titles in result order */
	private function search(?string $keyword, ?string $orderBy = null): array
	{
		$result = (new PostQueries(array_filter(['q' => $keyword, 'orderBy' => $orderBy])))->fetch();
		
		return collect(data_get($result, 'posts.data', []))->pluck('title')->all();
	}
	
	private function listing(int $id, string $title, string $description, int $categoryId, $createdAt): void
	{
		DB::table('posts')->insert([
			'id' => $id, 'country_code' => 'US', 'user_id' => null, 'category_id' => $categoryId, 'city_id' => 1, 'post_type_id' => 1,
			'title' => $title, 'description' => $description, 'price' => 100, 'currency_code' => 'USD', 'contact_name' => 'Sam',
			'email' => 'sam@example.test', 'auth_field' => 'email', 'email_verified_at' => now(), 'phone_verified_at' => now(),
			'reviewed_at' => now(), 'moderation_status' => 'approved', 'created_at' => $createdAt, 'updated_at' => $createdAt,
		]);
	}
}
