<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Safety page (docs/professional-website-plan.md, phase 4).
 *
 * The anti-scam page becomes the site's "tips" page, so the safety card on listings links to it,
 * and its stock English text is replaced by a current guide. Text the owner already edited is kept:
 * only the stock version is replaced. down() restores the stock text and the page type.
 */
return new class extends Migration
{
	private const SLUG = 'anti-scam';
	private const ORIGINAL_FILE = 'data/anti-scam-page-original.json';

	/** A phrase only the stock text has. */
	private const STOCK_MARKER = 'Western Union';

	public function up(): void
	{
		$page = $this->page();
		if (!$page) {
			return;
		}

		$content = json_decode((string)$page->content, true);
		$content = is_array($content) ? $content : [];
		$isStock = str_contains((string)($content['en'] ?? ''), self::STOCK_MARKER);

		$changes = ['type' => 'tips'];
		if ($isStock) {
			$changes['content'] = json_encode(array_merge($content, ['en' => $this->guide()]), JSON_UNESCAPED_UNICODE);
		}

		DB::table('pages')->where('id', $page->id)->update($changes);
		$this->forgetCache();
	}

	public function down(): void
	{
		$page = $this->page();
		if (!$page) {
			return;
		}

		$changes = ['type' => 'standard'];
		$original = @file_get_contents(database_path(self::ORIGINAL_FILE));
		$content = json_decode((string)$page->content, true);
		if ($original !== false && is_array($content) && str_contains((string)($content['en'] ?? ''), 'Buy and sell safely')) {
			$changes['content'] = $original;
		}

		DB::table('pages')->where('id', $page->id)->update($changes);
		$this->forgetCache();
	}

	private function page(): ?object
	{
		return Schema::hasTable('pages') ? DB::table('pages')->where('slug', self::SLUG)->first(['id', 'content']) : null;
	}

	private function forgetCache(): void
	{
		try {
			caching()->invalidate(Page::class);
		} catch (Throwable $e) {
			logger()->warning('Pages cache not invalidated after migration: ' . $e->getMessage());
		}
	}

	private function guide(): string
	{
		return <<<'HTML'
<h2>Buy and sell safely</h2>
<p>Most people here are honest. A few simple habits protect you from the rest, whether you are buying or selling.</p>

<h3>Before you meet</h3>
<ul>
<li><strong>Keep the conversation on the site.</strong> Use the built-in messages until you meet. Be careful when someone pushes you to move to another app straight away.</li>
<li><strong>Look at the seller.</strong> Check the "Verified seller" badge, how long they have been a member and their other listings.</li>
<li><strong>Ask questions.</strong> Ask for more photos, a serial number or a short video call. Honest sellers are happy to help.</li>
<li><strong>Be wary of a price far below the market.</strong> If a deal seems too good to be true, it usually is.</li>
</ul>

<h3>Meeting and paying</h3>
<ul>
<li><strong>Meet in a busy public place, in daylight.</strong> Bring a friend for valuable items, and tell someone where you are going.</li>
<li><strong>Check the item before you pay.</strong> Make sure it works and matches the listing.</li>
<li><strong>Pay when you collect the item.</strong> Never send a deposit, gift cards, cryptocurrency or a wire transfer to someone you have not met.</li>
<li><strong>Do not use payment links sent in messages.</strong> They often lead to fake payment pages.</li>
</ul>

<h3>Common scams</h3>
<ul>
<li>A buyer "overpays" and asks you to send back the difference.</li>
<li>A screenshot or email claims a payment was made, but no money has arrived.</li>
<li>Someone asks for a code sent to your phone or email. It is used to take over your accounts.</li>
<li>The seller is away or abroad and will ship the item once you pay.</li>
<li>A home for rent that you cannot visit before paying a deposit.</li>
<li>A link to a look-alike website that asks you to log in or enter card details.</li>
</ul>

<h3>Protect your account</h3>
<ul>
<li>Never share your password or the codes we send you. We will never ask for them.</li>
<li>Use a strong password that you do not use on other sites.</li>
</ul>

<h3>If something goes wrong</h3>
<ul>
<li>Stop replying and do not send money.</li>
<li>Use the <strong>Report abuse</strong> link on the listing, so we can review it.</li>
<li>If you have lost money, contact your bank and the local police straight away.</li>
<li>For anything else, <a href="/contact">contact us</a>.</li>
</ul>
HTML;
	}
};
