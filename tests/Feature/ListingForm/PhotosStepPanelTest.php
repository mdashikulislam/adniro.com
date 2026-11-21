<?php

namespace Tests\Feature\ListingForm;

use App\Services\ListingForm\PanelState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The multi-step Photos step shows the same side panel as the details step (adniro): listing quality,
 * the live buyer preview and the tips, filled from the details saved in step 1, with photos scored live.
 */

class PhotosStepPanelTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const OWNER_ID = 50;
	private const MULTI = 'multi-steps-form';

	private int $postId;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		config([
			'settings.listing_form.publication_form_type' => self::MULTI,
			'settings.listing_form.pictures_limit'        => 5,
		]);
		$this->stockMember(self::OWNER_ID);
		$this->postId = $this->stockListing('Grey corner sofa', ['user_id' => self::OWNER_ID])->id;
	}

	public function test_the_create_photos_step_shows_quality_preview_and_tips_from_step_one(): void
	{
		// Arrange: step 1 saved a car with a brand
		[$categoryId, $brandFieldId, $toyotaId] = $this->carWithBrand();
		$cityId = (int)DB::table('cities')->value('id');

		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->withSession(['postInput' => [
				'category_id' => $categoryId, 'title' => 'Toyota Corolla 2019', 'description' => '<p>One owner.</p><p>Full service history.</p>',
				'price' => '14500', 'negotiable' => '1', 'city_id' => $cityId, 'contact_name' => 'Rafi', 'email' => 'rafi@example.test',
				'tags' => ['corolla', 'toyota'], 'cf' => [$brandFieldId => $toyotaId],
			]])
			->get('/posts/create/photos');

		// Assert
		$response->assertOk();
		$state = $this->panelState($response);
		$this->assertSame('Toyota Corolla 2019', $state['state']['title']);
		$this->assertSame("One owner.\nFull service history.", $state['state']['description']);
		$this->assertSame('14500', $state['state']['price']);
		$this->assertTrue($state['state']['negotiable']);
		$this->assertSame('Springfield', $state['state']['cityName']);
		$this->assertSame('rafi@example.test', $state['state']['email']);
		$this->assertSame(['corolla', 'toyota'], $state['tags']);
		$this->assertContains('Toyota', array_merge(...array_column($state['details'], 'values')));
		$this->assertStringEndsWith('/posts/create', $state['backUrl'], 'rows for step 1 fields go back to step 1');
		$html = $response->getContent();
		foreach (['data-lf-strength', 'data-lf-live ', 'lf-tips', 'data-lf-check="photos"', 'id="lfSection-photos"'] as $part) {
			$this->assertStringContainsString($part, $html, $part);
		}
		$this->assertStringNotContainsString('offcanvas-lg', $html, 'on this page the panel sits under the photos on phones');
	}

	public function test_the_edit_photos_step_shows_the_saved_listing(): void
	{
		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->get('/posts/' . $this->postId . '/photos');

		// Assert
		$response->assertOk();
		$state = $this->panelState($response);
		$this->assertSame('Grey corner sofa', $state['state']['title']);
		$this->assertSame('Springfield', $state['state']['cityName']);
		$this->assertSame('sam@example.test', $state['state']['email']);
		$this->assertStringContainsString('/details', $state['backUrl']);
	}

	public function test_the_stock_layout_keeps_the_photos_step_as_it_was(): void
	{
		// Arrange
		config(['listing_form.v2' => false]);

		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->withSession(['postInput' => ['title' => 'Grey corner sofa']])
			->get('/posts/create/photos');

		// Assert
		$response->assertOk();
		$this->assertStringNotContainsString('data-lf-panel-state', $response->getContent());
	}

	public function test_a_description_becomes_plain_text_with_its_paragraphs(): void
	{
		// Act
		$state = app(PanelState::class)->fromInput(['description' => '<p>First line<br>second line</p><p>Third &amp; last</p>']);

		// Assert
		$this->assertSame("First line\nsecond line\nThird & last", $state['state']['description']);
	}

	public function test_missing_step_one_details_give_an_empty_but_usable_state(): void
	{
		// Act
		$state = app(PanelState::class)->fromInput([]);

		// Assert
		$this->assertSame(0, $state['state']['categoryId']);
		$this->assertSame('', $state['state']['title']);
		$this->assertTrue($state['state']['priceShown']);
		$this->assertSame([], $state['details']);
		$this->assertSame([], $state['tags']);
	}

	/**
	 * A category with its own "Car Brand" select field and a "Toyota" option. Created here, since the stock
	 * links between categories and fields use fixed ids that other tests' reseeding moves.
	 *
	 * @return array{0: int, 1: int, 2: int} the category id, the field id and the option id
	 */
	private function carWithBrand(): array
	{
		$categoryId = (int)DB::table('categories')->whereNotNull('parent_id')->where('active', 1)->value('id');
		$fieldId = DB::table('fields')->insertGetId([
			'belongs_to' => 'posts', 'name' => json_encode(['en' => 'Car Brand']), 'type' => 'select', 'active' => 1,
		]);
		$optionId = DB::table('fields_options')->insertGetId(['field_id' => $fieldId, 'value' => json_encode(['en' => 'Toyota'])]);
		DB::table('category_field')->insert(['category_id' => $categoryId, 'field_id' => $fieldId, 'disabled_in_subcategories' => 0]);

		return [$categoryId, $fieldId, $optionId];
	}

	private function panelState(TestResponse $response): array
	{
		preg_match('/data-lf-panel-state="([^"]+)"/', $response->getContent(), $match);
		$this->assertNotEmpty($match, 'the panel carries the saved details');

		return json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
	}
}
