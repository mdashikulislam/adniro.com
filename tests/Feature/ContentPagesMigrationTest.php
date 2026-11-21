<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * The phase 5 pages migration: adds pages, replaces only stock text, extends the privacy policy
 * once, and undoes exactly what it did (adniro).
 */

class ContentPagesMigrationTest extends TestCase
{
	use RefreshDatabase;
	
	private const MIGRATION = 'database/migrations/2026_11_21_000200_add_content_and_legal_pages.php';
	
	public function test_up_adds_pages_updates_the_policy_and_down_restores_it(): void
	{
		// Arrange
		$privacy = $this->privacyHtml();
		$this->page('privacy', 'privacy', $privacy, 1);
		$this->page('faq', 'standard', "<p><strong>How do I place an ad?</strong></p>\r\n<p>Click the button \"Post free Ads\".</p>", 3);
		DB::table('settings')->insert(['name' => 'other', 'label' => 'Other', 'field_values' => json_encode(['cookie_consent_enabled' => '0']), 'active' => 1]);
		$migration = require base_path(self::MIGRATION);
		
		// Act
		$migration->up();
		
		// Assert
		$this->assertSame(['about-us', 'cookie-policy', 'faq', 'posting-rules', 'privacy'], DB::table('pages')->orderBy('slug')->pluck('slug')->all());
		$this->assertStringContainsString('Frequently asked questions', $this->english('faq'));
		$updated = $this->english('privacy');
		$this->assertStringContainsString('AI Features', $updated);
		$this->assertMatchesRegularExpression('#<!-- Section 12 -->.*?>12</div>\s*<h2[^>]*>Changes#s', $updated);
		$this->assertStringContainsString('deleted after 48 hours', $updated);
		$this->assertStringContainsString('November 2026', $updated);
		$this->assertSame('1', json_decode(DB::table('settings')->where('name', 'other')->value('field_values'), true)['cookie_consent_enabled']);
		
		// Act again: running twice changes nothing more
		$migration->up();
		$this->assertSame($updated, $this->english('privacy'));
		
		// Act: undo
		$migration->down();
		
		// Assert
		$this->assertSame($privacy, $this->english('privacy'));
		$this->assertSame(['faq', 'privacy'], DB::table('pages')->orderBy('slug')->pluck('slug')->all());
		$this->assertSame('0', json_decode(DB::table('settings')->where('name', 'other')->value('field_values'), true)['cookie_consent_enabled']);
	}
	
	public function test_a_faq_the_owner_rewrote_is_kept(): void
	{
		// Arrange
		$this->page('faq', 'standard', '<p>Our own answers.</p>', 1);
		
		// Act
		(require base_path(self::MIGRATION))->up();
		
		// Assert
		$this->assertSame('<p>Our own answers.</p>', $this->english('faq'));
	}
	
	private function privacyHtml(): string
	{
		$section = fn (int $n, string $title, string $body) => "<!-- Section {$n} -->\r\n<div style=\"background: white;\">\r\n<div style=\"display: flex;\">\r\n<div style=\"background: #2c3e50;\">{$n}</div>\r\n<h2 style=\"margin: 0;\">{$title}</h2>\r\n</div>\r\n{$body}\r\n</div>\r\n";
		
		return "<p><strong>Last Updated:</strong> October, 2025</p>\r\n"
			. $section(6, 'Data Retention', "<ul style=\"padding-left: 20px;\">\r\n<li>Provide our services</li>\r\n</ul>")
			. $section(9, 'External Links', '<p>Links.</p>')
			. $section(10, 'Changes to This Privacy Policy', '<p>Changes.</p>')
			. $section(11, 'Contact Us', '<p>Email us.</p>');
	}
	
	private function page(string $slug, string $type, string $html, int $lft): void
	{
		DB::table('pages')->insert([
			'name' => json_encode(['en' => ucfirst($slug)]), 'title' => json_encode(['en' => ucfirst($slug)]), 'slug' => $slug,
			'type' => $type, 'content' => json_encode(['en' => $html]), 'lft' => $lft, 'rgt' => $lft + 1, 'depth' => 0, 'active' => 1,
		]);
	}
	
	private function english(string $slug): string
	{
		return json_decode(DB::table('pages')->where('slug', $slug)->value('content'), true)['en'];
	}
}
