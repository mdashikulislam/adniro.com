<?php

namespace Tests\Feature\ListingForm;

use App\Models\User;
use Tests\TestCase;

/*
 * The live buyer preview in the listing form's side panel (adniro): the listing as buyers will see it,
 * as a feed card or a detail view, filled from the form's own fields by listing-form/preview.js.
 */

class LivePreviewTest extends TestCase
{
	private const NEW_KEYS = [
		'live_title', 'live_view', 'live_feed', 'live_detail', 'live_title_empty', 'live_specs_empty',
		'live_seller_empty', 'live_cta', 'live_contact', 'live_just_now', 'live_verified',
		'live_photo_next', 'live_photo_below', 'live_description_empty',
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

	public function test_the_preview_switches_between_a_feed_card_and_a_detail_view(): void
	{
		// Act
		$html = $this->renderPanel(false);

		// Assert
		$this->assertMatchesRegularExpression('/<button[^>]*data-lf-view="feed"[^>]*aria-pressed="true"/', $html);
		$this->assertMatchesRegularExpression('/<button[^>]*data-lf-view="detail"[^>]*aria-pressed="false"/', $html);
		foreach (['category', 'price', 'condition', 'title', 'description', 'city', 'seller', 'initials', 'negotiable'] as $slot) {
			$this->assertStringContainsString('data-lf-live="' . $slot . '"', $html, $slot);
		}
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

	public function test_the_verified_tick_follows_the_sites_verified_email_rule(): void
	{
		// Act
		$guest = $this->renderPanel(false);
		$this->actingAs(new User(['name' => 'Rafi Ahmed', 'email_verified_at' => now()]));
		$verified = $this->renderPanel(false);
		$this->actingAs(new User(['name' => 'Rafi Ahmed', 'email_verified_at' => null]));
		$unverified = $this->renderPanel(false);

		// Assert
		$this->assertStringContainsString('data-lf-seller-verified="0"', $guest);
		$this->assertStringContainsString('data-lf-seller-verified="1"', $verified);
		$this->assertStringContainsString('Rafi Ahmed', $verified, 'the account name until the form says otherwise');
		$this->assertStringContainsString('data-lf-seller-verified="0"', $unverified);
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

		// Assert: seller name, custom fields (also through select2), photos, and the view switch
		foreach (["rawValue('contact_name')", '#cfContainer', "on('change', '#cfContainer select'", '#lfSection-photos', 'data-lf-view', "aria-pressed"] as $hook) {
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
