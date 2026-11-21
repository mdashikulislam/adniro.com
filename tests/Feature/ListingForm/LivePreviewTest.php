<?php

namespace Tests\Feature\ListingForm;

use Tests\TestCase;

/*
 * The live buyer preview in the listing form's side panel (adniro): the site's own listing card (as in
 * search results) and listing page, filled from the form's own fields by listing-form/preview.js.
 */

class LivePreviewTest extends TestCase
{
	private const NEW_KEYS = [
		'live_title', 'live_view', 'live_feed', 'live_detail', 'live_title_empty',
		'live_just_now', 'live_photo_next', 'live_photo_below', 'live_description_empty',
	];

	public function test_the_preview_comes_after_the_listing_quality_card(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert
		$quality = strpos($html, 'data-lf-strength');
		$live = strpos($html, 'data-lf-live ');
		$this->assertNotFalse($live, 'the live preview');
		$this->assertGreaterThan($quality, $live, 'after listing quality');
		$this->assertStringNotContainsString('lf-preview-card', $html, 'the old small preview is gone');
	}

	public function test_the_feed_view_is_the_sites_own_listing_card(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert: the classes of front/search/partials/posts/template/grid.blade.php
		foreach (['listing-card ', 'listing-card-media', 'listing-card-picture', 'listing-card-location', 'listing-card-cat', 'listing-card-title', 'listing-card-desc', 'listing-card-price', 'listing-card-date'] as $class) {
			$this->assertStringContainsString($class, $html, $class);
		}
	}

	public function test_the_detail_view_is_the_sites_own_listing_page(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert: the classes of front/post/show/index.blade.php and its details partials, in the page's order
		$order = ['listing-header', 'listing-chip-cat', 'listing-price-card', 'listing-title', 'listing-meta', 'listing-gallery', 'listing-description', 'listing-tags', 'listing-specs'];
		$positions = array_map(fn ($class) => strpos($html, $class), $order);
		$this->assertNotContains(false, $positions, implode(', ', $order));
		$sorted = $positions;
		sort($sorted);
		$this->assertSame($sorted, $positions, 'same order as the listing page');
		$this->assertStringContainsString(e(t('Additional Details')), $html);
	}

	public function test_the_preview_switches_between_the_card_and_the_page(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert
		$this->assertMatchesRegularExpression('/<button[^>]*data-lf-view="feed"[^>]*aria-pressed="true"/', $html);
		$this->assertMatchesRegularExpression('/<button[^>]*data-lf-view="detail"[^>]*aria-pressed="false"/', $html);
	}

	public function test_the_photo_area_says_where_photos_are_added_in_each_form_mode(): void
	{
		// Act
		$multiStep = $this->renderPanel(false);
		$singleStep = $this->renderPanel(true);

		// Assert
		$this->assertStringContainsString(e(trans('listing_form.live_photo_next')), $multiStep);
		$this->assertStringContainsString(e(trans('listing_form.live_photo_below')), $singleStep);
	}

	public function test_every_translation_has_the_live_preview_texts(): void
	{
		// Arrange
		$english = require lang_path('en/listing_form.php');

		foreach (glob(lang_path('*/listing_form.php')) as $file) {
			// Act
			$strings = require $file;

			// Assert
			foreach (self::NEW_KEYS as $key) {
				$this->assertNotEmpty($strings[$key] ?? null, "{$file}: {$key}");
				if ($file !== lang_path('en/listing_form.php')) {
					$this->assertNotSame($english[$key], $strings[$key], "{$file}: {$key} is translated");
				}
			}
		}
	}

	public function test_the_script_fills_the_preview_from_the_forms_own_fields(): void
	{
		// Arrange
		$script = file_get_contents(public_path('assets/js/app/listing-form/preview.js'));

		// Assert: custom fields (also through select2), tags, photos, and the view switch
		foreach (['#cfContainer', "select[name=\"tags[]\"]", '#lfSection-photos', 'listing-spec-label', 'data-lf-view', 'aria-pressed'] as $hook) {
			$this->assertStringContainsString($hook, $script, $hook);
		}
		$this->assertStringNotContainsString('innerHTML', $script, 'typed text is never read as HTML');
	}

	private function renderPanel(bool $singleStep): string
	{
		return view('front.post.createOrEdit.form.panel', [
			'listingForm'   => (object)['isEdit' => false],
			'isSingleStep'  => $singleStep,
			'picturesLimit' => 5,
		])->render();
	}
}
