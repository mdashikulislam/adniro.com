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
		'live_title', 'live_title_empty', 'live_just_now', 'live_photo_next', 'live_photo_below', 'live_photo_here',
	];

	public function test_the_preview_comes_after_the_listing_quality_card(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert
		$quality = strpos($html, 'data-lf-strength');
		$live = strpos($html, 'aria-labelledby="lfLiveTitle"');
		$this->assertNotFalse($live, 'the live preview');
		$this->assertGreaterThan($quality, $live, 'after listing quality');
		$this->assertStringNotContainsString('lf-preview-card', $html, 'the old small preview is gone');
		$this->assertStringContainsString('lf-tips', $html, 'the details step keeps the selling tips');
	}

	public function test_the_preview_is_the_sites_own_listing_card(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert: the classes of front/search/partials/posts/template/grid.blade.php
		foreach (['listing-card ', 'listing-card-media', 'listing-card-picture', 'listing-card-location', 'listing-card-cat', 'listing-card-title', 'listing-card-desc', 'listing-card-price', 'listing-card-date'] as $class) {
			$this->assertStringContainsString($class, $html, $class);
		}
	}

	public function test_only_the_listing_card_is_shown(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert: no detail view, and no switch between views
		$this->assertStringNotContainsString('data-lf-view', $html);
		$this->assertStringNotContainsString('listing-header', $html);
		$this->assertStringNotContainsString('listing-specs', $html);
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

		// Assert: the form state, and the photos on the page
		foreach (['LF.onChange(draw)', '#lfSection-photos', 'data-lf-live'] as $hook) {
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
