<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The pricing page (front/pages/pricing.blade.php): every plan card is drawn from the package's admin
 * options (short name, price, promotion days, photos, description lines, ribbon colour, recommended).
 */
class PricingPageTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const URL = '/pricing';

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();

		// The live plans: Free, Starter, Plus (recommended), Premium; no subscriptions
		DB::table('packages')->where('type', 'subscription')->update(['active' => 0]);
		DB::table('packages')->where('type', 'promotion')->delete();
		$plans = [
			['Free', '0.00', null, null, 'danger', 0, null, 1],
			['Starter', '4.00', 7, 10, 'warning', 0, "Featured on the homepage\nFeatured in the category", 2],
			['Plus', '6.00', 15, 10, 'success', 1, "Featured on the homepage\nFeatured in the category", 3],
			['Premium', '10.00', 30, 10, 'info', 0, 'Featured on the homepage', 4],
		];
		foreach ($plans as [$name, $price, $days, $photos, $ribbon, $recommended, $description, $position]) {
			DB::table('packages')->insert([
				'type' => 'promotion', 'name' => json_encode(['en' => $name . ' Boost']), 'short_name' => json_encode(['en' => $name]),
				'ribbon' => $ribbon, 'has_badge' => 1, 'price' => $price, 'currency_code' => 'USD', 'promotion_time' => $days,
				'pictures_limit' => $photos, 'expiration_time' => $days ? 365 : null,
				'description' => $description ? json_encode(['en' => $description]) : null,
				'recommended' => $recommended, 'lft' => $position, 'active' => 1,
			]);
		}
	}

	private function page(): string
	{
		$response = $this->get(self::URL);
		$response->assertOk();

		return $response->getContent();
	}

	public function test_the_page_brings_its_own_stylesheet_and_a_premium_head(): void
	{
		// Act
		$html = $this->page();

		// Assert
		$this->assertStringContainsString('assets/css/pricing.css?v=', $html);
		$this->assertStringContainsString('class="container pr-page"', $html);
		$this->assertMatchesRegularExpression('~<h1 class="pr-hero-title"[^>]*>.*<strong>more buyers</strong>.*</h1>~s', $html);
		$this->assertMatchesRegularExpression('~<p class="pr-hero-eyebrow"[^>]*>.*Pricing.*</p>~s', $html);
	}

	public function test_the_highlights_come_from_the_plans(): void
	{
		// Act
		$html = $this->page();

		// Assert: the cheapest price, the longest promotion and the most photos of the active plans
		$this->assertMatchesRegularExpression('~class="pr-highlight"[^>]*>.*Plans from.*\$0.*</li>~sU', $html);
		$this->assertMatchesRegularExpression('~class="pr-highlight"[^>]*>.*Up to 30 days of promotion.*</li>~sU', $html);
		$this->assertMatchesRegularExpression('~class="pr-highlight"[^>]*>.*Up to 10 photos.*</li>~sU', $html);
	}

	public function test_every_active_plan_is_a_card_with_its_price_and_features(): void
	{
		// Act
		$html = $this->page();

		// Assert
		$this->assertSame(4, substr_count($html, '<article class="pr-card'));
		foreach (['Free', 'Starter', 'Plus', 'Premium'] as $name) {
			$this->assertMatchesRegularExpression('~<h3 class="pr-card-name">\s*' . $name . '\s*</h3>~', $html);
		}
		$this->assertMatchesRegularExpression('~class="pr-card-amount">\s*\$10\s*<~', $html);
		$this->assertStringContainsString('30-day boost', $html);
		$this->assertMatchesRegularExpression('~<li class="pr-feature">\s*<i class="bi bi-check-circle-fill[^"]*" aria-hidden="true"></i>\s*<span>Featured in the category</span>~', $html);
	}

	public function test_the_recommended_plan_stands_out_with_a_most_popular_badge(): void
	{
		// Act
		$html = $this->page();

		// Assert: exactly one, on the Plus card
		$this->assertSame(1, substr_count($html, 'pr-card--featured'));
		$this->assertMatchesRegularExpression('~<article class="pr-card pr-card--featured"[^>]*>\s*<span class="pr-card-badge">.*Most popular.*</span>.*<h3 class="pr-card-name">\s*Plus\s*</h3>~sU', $html);
	}

	public function test_a_paid_card_takes_its_ribbon_colour_and_the_free_card_stays_neutral(): void
	{
		// Act
		$html = $this->page();

		// Assert
		$this->assertStringContainsString('style="--pr-tier: var(--bs-warning-rgb)"', $html);
		$this->assertStringContainsString('style="--pr-tier: var(--bs-info-rgb)"', $html);
		$this->assertStringNotContainsString('--bs-danger-rgb', $html);
	}

	public function test_each_card_starts_a_listing_with_its_plan(): void
	{
		// Arrange
		$plusId = (int)DB::table('packages')->where('short_name->en', 'Plus')->value('id');
		$freeId = (int)DB::table('packages')->where('short_name->en', 'Free')->value('id');

		// Act
		$html = $this->page();

		// Assert: guests get the sign-in pop-up when they cannot post, members go to the form with the plan
		$this->assertMatchesRegularExpression('~href="[^"]*(packageId=' . $plusId . '|\#quickLogin)"[^>]*class="pr-card-cta pr-card-cta--solid"~', $html);
		$this->assertStringContainsString('Choose Plus', $html);
		$this->assertStringContainsString('Post for free', $html);
		$this->assertMatchesRegularExpression('~href="[^"]*(packageId=' . $freeId . '|\#quickLogin)"~', $html);
	}

	public function test_the_page_explains_how_a_boost_works_and_where_to_ask(): void
	{
		// Act
		$html = $this->page();

		// Assert
		$this->assertSame(3, substr_count($html, '<li class="pr-step">'));
		$this->assertStringContainsString('Pick a plan', $html);
		$this->assertMatchesRegularExpression('~<a[^>]+href="[^"]*/contact"[^>]*class="pr-help-link"~', $html);
	}

	public function test_a_plan_without_a_short_name_uses_its_full_name(): void
	{
		// Arrange
		DB::table('packages')->where('short_name->en', 'Starter')->update(['short_name' => null]);

		// Act
		$html = $this->page();

		// Assert
		$this->assertMatchesRegularExpression('~<h3 class="pr-card-name">\s*Starter Boost\s*</h3>~', $html);
		$this->assertStringContainsString('Choose Starter Boost', $html);
	}

	public function test_a_ribbon_colour_outside_bootstrap_never_reaches_the_style(): void
	{
		// Arrange
		DB::table('packages')->where('short_name->en', 'Starter')->update(['ribbon' => 'red;background:url(x)']);

		// Act
		$html = $this->page();

		// Assert: Starter is neutral; only Plus and Premium keep their colour
		$this->assertStringNotContainsString('url(x)', $html);
		$this->assertSame(2, substr_count($html, 'style="--pr-tier:'));
	}

	public function test_with_subscriptions_too_the_tabs_show_and_the_query_picks_one(): void
	{
		// Arrange: a free and a monthly subscription
		DB::table('packages')->where('type', 'subscription')->update(['active' => 1, 'interval' => 'month']);

		// Act
		$promotion = $this->get(self::URL . '?type=promotion')->assertOk()->getContent();
		$subscription = $this->get(self::URL . '?type=subscription')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('class="nav pr-tabs"', $promotion);
		$this->assertMatchesRegularExpression('~class="nav-link active"\s+id="pills-promotion-tab"~', $promotion);
		$this->assertMatchesRegularExpression('~class="nav-link active"\s+id="pills-subscription-tab"~', $subscription);
		$this->assertMatchesRegularExpression('~<span class="pr-card-period">per month</span>~i', $subscription);
		$this->assertStringContainsString('/subscription?packageId=', $subscription);
		// A free subscription does not promise a free listing
		$this->assertMatchesRegularExpression('~href="[^"]*/subscription\?packageId=\d+"[^>]*>\s*<span>Get started</span>~', $subscription);
	}

	public function test_no_plans_shows_a_friendly_empty_card(): void
	{
		// Arrange
		DB::table('packages')->update(['active' => 0]);

		// Act
		$html = $this->page();

		// Assert
		$this->assertStringContainsString('class="pr-empty"', $html);
		$this->assertStringNotContainsString('<article class="pr-card', $html);
	}
}
