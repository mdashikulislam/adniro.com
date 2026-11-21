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
		'strength_next', 'strength_photos_next',
		'hint_category_todo', 'hint_title_empty', 'hint_title_short', 'hint_title_long', 'hint_title_good',
		'hint_description_empty', 'hint_description_short', 'hint_description_good',
		'hint_price_todo', 'hint_price_negotiable', 'hint_city_todo', 'hint_contact_todo', 'hint_contact_done',
		'next_category', 'next_title', 'next_description', 'next_price', 'next_city', 'next_contact',
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

	public function test_the_panel_has_a_meter_a_next_step_and_live_hints(): void
	{
		// Act
		$html = file_get_contents(resource_path('views/front/post/createOrEdit/form/panel.blade.php'));

		// Assert
		$this->assertStringContainsString('role="meter"', $html);
		$this->assertStringContainsString('data-lf-next', $html);
		$this->assertStringContainsString('data-lf-hint', $html);
		$this->assertStringContainsString('data-lf-points', $html);
		$this->assertStringNotContainsString("trans('listing_form.checklist')", $html);
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
			foreach (['checklist', 'checklist_progress', 'checklist_ready'] as $old) {
				$this->assertArrayNotHasKey($old, $strings, "{$file}: {$old} is no longer used");
			}
		}
	}
}
