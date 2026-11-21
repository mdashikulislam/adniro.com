<?php

namespace Tests\Feature\ListingForm;

use App\Helpers\Common\Files\Storage\StorageDisk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Going back from the multi-step Photos step to the details step (adniro): the saved category is shown
 * as chosen (with its custom fields), and the live preview shows the photos already added.
 */

class BackToDetailsTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const OWNER_ID = 50;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		config([
			'settings.listing_form.publication_form_type' => 'multi-steps-form',
			'settings.listing_form.pictures_limit'        => 5,
		]);
		$this->stockMember(self::OWNER_ID);
	}

	public function test_a_category_saved_in_step_one_is_shown_as_chosen_on_the_way_back(): void
	{
		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->withSession(['postInput' => ['category_id' => 2, 'title' => 'Toyota Corolla 2019']])
			->get('/posts/create');

		// Assert: d.modal.category.js loads the category's name and fields when this is true
		$response->assertOk();
		$this->assertMatchesRegularExpression('/categoryWasSelected = true;/', $response->getContent());
	}

	public function test_a_new_listing_starts_without_a_chosen_category(): void
	{
		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))->get('/posts/create');

		// Assert
		$response->assertOk();
		$this->assertDoesNotMatchRegularExpression('/categoryWasSelected = true;/', $response->getContent());
	}

	public function test_the_preview_shows_the_first_photo_already_added(): void
	{
		// Arrange: two photos uploaded on the Photos step, kept in the session
		Storage::fake(StorageDisk::getDiskName());
		Storage::disk(StorageDisk::getDiskName())->put('temporary/abc123/corolla-front.jpg', UploadedFile::fake()->image('front.jpg')->getContent());

		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->withSession([
				'postInput'     => ['category_id' => 2, 'title' => 'Toyota Corolla 2019'],
				'picturesInput' => ['temporary/abc123/corolla-front.jpg', 'temporary/abc123/corolla-back.jpg'],
			])
			->get('/posts/create');

		// Assert
		$response->assertOk();
		$this->assertMatchesRegularExpression('/data-lf-cover="[^"]*corolla-front\.jpg"/', $response->getContent());
		$this->assertStringContainsString('data-lf-photo-count="2"', $response->getContent());
	}

	public function test_the_edit_details_step_counts_the_listings_photos_and_shows_its_cover(): void
	{
		// Arrange: a saved listing with two photos
		Storage::fake(StorageDisk::getDiskName());
		$post = $this->stockListing('Apple iPhone 17 Pro', ['user_id' => self::OWNER_ID]);
		foreach (['files/us/' . $post->id . '/front.jpg', 'files/us/' . $post->id . '/back.jpg'] as $position => $path) {
			Storage::disk(StorageDisk::getDiskName())->put($path, UploadedFile::fake()->image(basename($path))->getContent());
			\Illuminate\Support\Facades\DB::table('pictures')->insert([
				'post_id' => $post->id, 'file_path' => $path, 'mime_type' => 'image/jpeg', 'position' => $position, 'active' => 1,
				'created_at' => now(), 'updated_at' => now(),
			]);
		}

		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))->get('/posts/' . $post->id . '/details');

		// Assert
		$response->assertOk();
		$this->assertStringContainsString('data-lf-photo-count="2"', $response->getContent());
		$this->assertMatchesRegularExpression('/data-lf-cover="[^"]*front\.jpg"/', $response->getContent());
	}

	public function test_the_edit_page_opens_the_panel_with_a_card_to_manage_the_listing(): void
	{
		// Arrange: a saved listing with one photo
		Storage::fake(StorageDisk::getDiskName());
		$post = $this->stockListing('Apple iPhone 17 Pro', ['user_id' => self::OWNER_ID]);
		\Illuminate\Support\Facades\DB::table('pictures')->insert([
			'post_id' => $post->id, 'file_path' => 'files/us/' . $post->id . '/front.jpg', 'mime_type' => 'image/jpeg', 'position' => 0,
			'active' => 1, 'created_at' => now(), 'updated_at' => now(),
		]);

		// Act
		$html = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))->get('/posts/' . $post->id . '/details')->getContent();

		// Assert: first in the panel, before listing quality; the listing, its photos (with the count) and no old buttons
		$manage = strpos($html, 'class="lf-panel-card lf-manage"');
		$this->assertNotFalse($manage);
		$this->assertLessThan(strpos($html, 'data-lf-strength'), $manage);
		$this->assertStringContainsString('href="' . url('posts/' . $post->id . '/photos') . '" class="lf-manage-item"', $html);
		$this->assertStringContainsString(e(trans('listing_form.manage_photos_hint', ['count' => 1])), $html);
		$this->assertStringNotContainsString('lf-panel-actions', $html);
	}

	public function test_a_new_listing_has_no_manage_card(): void
	{
		// Act
		$html = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))->get('/posts/create')->getContent();

		// Assert
		$this->assertStringNotContainsString('lf-manage', $html);
	}

	public function test_a_photo_that_is_gone_shows_the_add_photos_hint_not_a_default_picture(): void
	{
		// Arrange: the session still names a photo whose temporary file was cleaned up
		Storage::fake(StorageDisk::getDiskName());

		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->withSession(['postInput' => ['category_id' => 2], 'picturesInput' => ['temporary/gone/old.jpg']])
			->get('/posts/create');

		// Assert
		$response->assertOk();
		$this->assertStringContainsString('data-lf-cover=""', $response->getContent());
	}
}
