<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Content and legal pages (docs/professional-website-plan.md, phase 5). Texts are in
 * database/data/pages, in English; other languages show the English text until translated.
 *
 * - Adds About us, Posting rules and Cookie policy (only when the slug is free).
 * - Replaces the stock FAQ (only while it is the stock text).
 * - Adds "AI Features" and "Analytics and Cookies" to the privacy policy, retention times to its
 *   "Data Retention" section, and renumbers the sections after them (only once, marked with comments).
 * - Turns on the cookie banner, which now gates the footer tracking code.
 *
 * down() undoes each change that is still as this migration left it.
 */
return new class extends Migration
{
	private const NEW_PAGES = [
		'about-us'      => ['en' => 'About us', 'fr' => 'À propos', 'es' => 'Sobre nosotros', 'de' => 'Über uns', 'it' => 'Chi siamo', 'ru' => 'О нас', 'ar' => 'من نحن', 'hi' => 'हमारे बारे में', 'bn' => 'আমাদের সম্পর্কে'],
		'posting-rules' => ['en' => 'Posting rules', 'fr' => 'Règles de publication', 'es' => 'Normas de publicación', 'de' => 'Regeln für Anzeigen', 'it' => 'Regole di pubblicazione', 'ru' => 'Правила размещения', 'ar' => 'قواعد النشر', 'hi' => 'पोस्टिंग नियम', 'bn' => 'পোস্টিংয়ের নিয়ম'],
		'cookie-policy' => ['en' => 'Cookie policy', 'fr' => 'Politique de cookies', 'es' => 'Política de cookies', 'de' => 'Cookie-Richtlinie', 'it' => 'Informativa sui cookie', 'ru' => 'Политика cookie', 'ar' => 'سياسة ملفات تعريف الارتباط', 'hi' => 'कुकी नीति', 'bn' => 'কুকি নীতি'],
	];

	private const FAQ_STOCK_MARKER = 'Post free Ads';
	private const FAQ_NEW_MARKER = 'Frequently asked questions';
	private const PRIVACY_START = '<!-- adniro:privacy-ai-start -->';
	private const PRIVACY_END = '<!-- adniro:privacy-ai-end -->';
	private const RETENTION_START = '<!-- adniro:retention-start -->';
	private const RETENTION_END = '<!-- adniro:retention-end -->';

	/** Old number => new number of the privacy sections after the two new ones. */
	private const PRIVACY_RENUMBER = [10 => 12, 11 => 13];

	public function up(): void
	{
		if (!Schema::hasTable('pages')) {
			return;
		}

		foreach (self::NEW_PAGES as $slug => $names) {
			$this->createPage($slug, $names);
		}
		$this->replaceStockFaq();
		$this->extendPrivacy();
		$this->setCookieBanner('1');
		$this->forgetCache();
	}

	public function down(): void
	{
		if (!Schema::hasTable('pages')) {
			return;
		}

		foreach (array_keys(self::NEW_PAGES) as $slug) {
			$content = $this->englishContent($slug);
			if (!is_null($content) && $content === $this->text($slug . '.html')) {
				DB::table('pages')->where('slug', $slug)->delete();
			}
		}

		$faq = $this->englishContent('faq');
		$original = @file_get_contents(database_path('data/faq-page-original.json'));
		if (!is_null($faq) && str_contains($faq, self::FAQ_NEW_MARKER) && $original !== false) {
			DB::table('pages')->where('slug', 'faq')->update(['content' => $original]);
		}

		$this->restorePrivacy();
		$this->setCookieBanner('0');
		$this->forgetCache();
	}

	private function createPage(string $slug, array $names): void
	{
		if (DB::table('pages')->where('slug', $slug)->exists()) {
			return; // the owner already has a page here
		}

		$lft = (int)DB::table('pages')->max('rgt') + 1;
		$namesJson = json_encode($names, JSON_UNESCAPED_UNICODE);

		DB::table('pages')->insert([
			'parent_id'  => null,
			'type'       => 'standard',
			'name'       => $namesJson,
			'slug'       => $slug,
			'title'      => $namesJson,
			'content'    => json_encode(['en' => $this->text($slug . '.html')], JSON_UNESCAPED_UNICODE),
			'lft'        => $lft,
			'rgt'        => $lft + 1,
			'depth'      => 0,
			'active'     => 1,
			'created_at' => now(),
			'updated_at' => now(),
		]);
	}

	private function replaceStockFaq(): void
	{
		$current = $this->englishContent('faq');
		if (is_null($current) || !str_contains($current, self::FAQ_STOCK_MARKER)) {
			return;
		}

		$this->updateEnglish('faq', $this->text('faq.html'));
	}

	private function extendPrivacy(): void
	{
		$html = $this->englishContent('privacy');
		if (is_null($html) || str_contains($html, self::PRIVACY_START)) {
			return;
		}

		// Renumber the last sections first, then insert the two new ones before "Section 12" (was 10)
		foreach (array_reverse(self::PRIVACY_RENUMBER, true) as $old => $new) {
			$html = $this->renumberSection($html, $old, $new);
		}
		$anchor = '<!-- Section ' . self::PRIVACY_RENUMBER[10] . ' -->';
		if (!str_contains($html, $anchor)) {
			return; // not the expected structure: leave the policy as it is
		}
		$html = str_replace($anchor, trim($this->text('privacy-additions.html')) . "\n" . $anchor, $html);

		// Retention times, added to the "Data Retention" list
		$html = preg_replace(
			'#(Data Retention</h2>.*?)(</ul>)#s',
			'$1' . self::RETENTION_START . $this->retentionItems() . self::RETENTION_END . '$2',
			$html,
			1
		) ?? $html;

		$html = preg_replace('#(Last Updated:(?:</strong>)?)\s*([A-Za-z]+,? \d{4})#', '$1 November 2026<!-- adniro:updated-was:$2 -->', $html, 1) ?? $html;

		$this->updateEnglish('privacy', $html);
	}

	private function restorePrivacy(): void
	{
		$html = $this->englishContent('privacy');
		if (is_null($html) || !str_contains($html, self::PRIVACY_START)) {
			return;
		}

		// Exactly what up() inserted: the block and the line break after it
		$html = preg_replace('#' . preg_quote(self::PRIVACY_START, '#') . '.*?' . preg_quote(self::PRIVACY_END, '#') . '\n#s', '', $html) ?? $html;
		$html = preg_replace('#' . preg_quote(self::RETENTION_START, '#') . '.*?' . preg_quote(self::RETENTION_END, '#') . '#s', '', $html) ?? $html;
		foreach (self::PRIVACY_RENUMBER as $old => $new) {
			$html = $this->renumberSection($html, $new, $old);
		}
		$html = preg_replace('#\s*November 2026<!-- adniro:updated-was:([^>]+) -->#', ' $1', $html, 1) ?? $html;

		$this->updateEnglish('privacy', $html);
	}

	/** "<!-- Section N -->" and the number badge that follows it. */
	private function renumberSection(string $html, int $from, int $to): string
	{
		return preg_replace(
			'#<!-- Section ' . $from . ' -->(\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>)' . $from . '(</div>)#',
			'<!-- Section ' . $to . ' -->${1}' . $to . '${2}',
			$html,
			1
		) ?? $html;
	}

	private function retentionItems(): string
	{
		return "\n" . '<li style="margin-top: 8px; margin-bottom: 8px;">Text you give the AI assistant, and its answer, are deleted after 48 hours. A record that an AI post was used, without any text, is kept for 35 days to apply the AI post limit.</li>'
			. "\n" . '<li style="margin-bottom: 8px;">Voice recordings are not stored after they have been turned into text.</li>'
			. "\n" . '<li style="margin-bottom: 8px;">The result of an automated or manual listing check, and its reason, are kept with the listing while it exists.</li>' . "\n";
	}

	private function englishContent(string $slug): ?string
	{
		$raw = DB::table('pages')->where('slug', $slug)->value('content');
		if (is_null($raw)) {
			return null;
		}
		$content = json_decode((string)$raw, true);

		return is_array($content) ? (string)($content['en'] ?? '') : null;
	}

	private function updateEnglish(string $slug, string $html): void
	{
		$content = json_decode((string)DB::table('pages')->where('slug', $slug)->value('content'), true);
		$content = is_array($content) ? $content : [];
		$content['en'] = $html;

		DB::table('pages')->where('slug', $slug)->update([
			'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
			'updated_at' => now(),
		]);
	}

	private function text(string $file): string
	{
		return (string)file_get_contents(database_path('data/pages/' . $file));
	}

	private function setCookieBanner(string $value): void
	{
		$row = DB::table('settings')->where('name', 'other')->first(['id', 'field_values']);
		if (!$row) {
			return;
		}
		$values = json_decode((string)$row->field_values, true);
		$values = is_array($values) ? $values : [];
		$values['cookie_consent_enabled'] = $value;
		DB::table('settings')->where('id', $row->id)->update(['field_values' => json_encode($values)]);
	}

	private function forgetCache(): void
	{
		try {
			caching()->invalidateMultiple([Page::class, \App\Models\Setting::class]);
			// The CMS page sidebar keeps its own list per language (resources/views/front/pages/cms.blade.php)
			foreach (DB::table('languages')->pluck('code') as $code) {
				cache()->forget('cms.pages.nav.' . $code);
			}
		} catch (Throwable $e) {
			logger()->warning('Cache not invalidated after the pages migration: ' . $e->getMessage());
		}
	}
};
