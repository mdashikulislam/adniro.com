<?php

namespace Tests\Feature\ListingForm;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/*
 * The listing strength panel of the redesigned listing form (adniro): a weighted score with tiers,
 * the one next step that helps most, and live hints per item. The scoring rules live in
 * strength-score.js and are tested with Node (tests/js/strength-score.test.cjs).
 */

class ListingStrengthTest extends TestCase
{
	private const NEW_KEYS = [
		'strength', 'strength_weak', 'strength_fair', 'strength_strong', 'strength_excellent',
		'strength_weak_text', 'strength_fair_text', 'strength_strong_text', 'strength_excellent_text',
		'strength_score', 'strength_photos_next',
		'hint_category_todo', 'hint_title_empty', 'hint_title_short', 'hint_title_long', 'hint_title_good',
		'hint_description_empty', 'hint_description_short', 'hint_description_good',
		'hint_price_todo', 'hint_price_negotiable', 'hint_city_todo', 'hint_contact_todo', 'hint_contact_done',
	];

	public function test_the_scoring_rules_pass_their_node_tests(): void
	{
		// Arrange
		$node = trim((string)shell_exec('command -v node'));
		if ($node === '') {
			$this->markTestSkipped('Node is not installed');
		}

		// Act
		$process = new Process([$node, '--test', base_path('tests/js/strength-score.test.cjs')]);
		$process->run();

		// Assert
		$this->assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
	}

	public function test_the_panel_shows_a_score_ring_a_tier_badge_and_what_each_item_is_worth(): void
	{
		// Act
		$html = file_get_contents(resource_path('views/front/post/createOrEdit/form/panel.blade.php'));

		// Assert
		$this->assertStringContainsString('class="lf-ring" role="meter"', $html);
		$this->assertStringContainsString('data-lf-ring', $html);
		$this->assertStringContainsString('class="lf-tier-badge" data-lf-tier', $html);
		$this->assertStringContainsString('data-lf-worth', $html);
		$this->assertStringNotContainsString('data-lf-next', $html);
	}

	public function test_row_labels_show_the_good_length_targets(): void
	{
		// Act
		$html = view('front.post.createOrEdit.form.panel', [
			'listingForm'   => (object)['isEdit' => false],
			'isSingleStep'  => false,
			'picturesLimit' => 5,
		])->render();

		// Assert
		$this->assertStringContainsString('Clear title (15–80 characters)', $html);
		$this->assertStringContainsString('Detailed description (200+ characters)', $html);
		$this->assertStringNotContainsString('{min}', $html);
	}

	public function test_the_scoring_script_loads_before_the_panel_script(): void
	{
		// Act
		$page = file_get_contents(resource_path('views/front/post/createOrEdit/form/page.blade.php'));
		preg_match("/\\\$lfScripts = \[(.*?)\];/", $page, $match);
		$scripts = array_map(fn ($name) => trim($name, " '"), explode(',', $match[1] ?? ''));

		// Assert
		$this->assertContains('strength-score', $scripts);
		$this->assertLessThan(array_search('panel', $scripts), array_search('strength-score', $scripts));
	}

	public function test_every_translation_has_the_strength_texts(): void
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
			foreach (['checklist', 'checklist_progress', 'checklist_ready', 'strength_next', 'next_title', 'next_description'] as $old) {
				$this->assertArrayNotHasKey($old, $strings, "{$file}: {$old} is no longer used");
			}
		}
	}
}
