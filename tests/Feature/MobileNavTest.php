<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The phone header controls and the bottom app bar (adniro): Home, Search, Sell, Saved and Account,
 * with the current page marked, guests sent to sign in, and no app bar on the listing form.
 */

class MobileNavTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const MEMBER_ID = 60;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		config(['settings.listing_form.publication_form_type' => 'multi-steps-form']);
	}

	public function test_a_guest_gets_the_app_bar_with_home_marked_and_sign_in_links(): void
	{
		// Arrange: guests may post, as on the live site
		config(['settings.listing_form.guest_can_submit_listings' => '1']);

		// Act
		$html = $this->get('/')->assertOk()->getContent();
		$appbar = $this->appbar($html);

		// Assert
		$this->assertMatchesRegularExpression('/<body[^>]*class="[^"]*has-appbar/', $html);
		$this->assertMatchesRegularExpression('/class="adn-appbar-link is-active"\s+aria-current="page"[^>]*>\s*<span class="adn-appbar-icon"[^>]*>\s*<i class="bi bi-house-door-fill"/', $appbar);
		$this->assertStringContainsString('href="' . url('posts/create') . '" class="adn-appbar-link adn-appbar-sell"', $appbar);
		$this->assertSame(2, substr_count($appbar, 'href="' . route('auth.login.showForm') . '"'));
		$this->assertStringContainsString('class="dropdown adn-mhead-account', $html);
	}

	public function test_the_header_account_button_opens_a_menu_of_account_links_for_a_member(): void
	{
		// Arrange
		$member = $this->stockMember(self::MEMBER_ID);

		// Act
		$menu = $this->accountMenu($this->actingAs($member)->get('/')->assertOk()->getContent());

		// Assert: a dropdown, with the member's name, account links and log out last
		$this->assertStringContainsString('data-bs-toggle="dropdown"', $menu);
		$this->assertStringContainsString('Member ' . self::MEMBER_ID, $menu);
		$this->assertStringContainsString('href="' . urlGen()->accountOverview() . '"', $menu);
		$this->assertStringContainsString('href="' . url('account/saved-posts') . '"', $menu);
		$this->assertSame(1, substr_count($menu, 'href="' . urlGen()->signOut() . '"'));
		$this->assertGreaterThan(strrpos($menu, urlGen()->accountOverview()), strpos($menu, 'adn-account-menu-logout'));
	}

	public function test_the_header_account_menu_offers_log_in_and_sign_up_to_a_guest(): void
	{
		// Act
		$menu = $this->accountMenu($this->get('/')->assertOk()->getContent());

		// Assert
		$this->assertStringContainsString('href="' . route('auth.login.showForm') . '"', $menu);
		$this->assertStringContainsString('href="' . route('auth.register.showForm') . '"', $menu);
	}

	public function test_a_guest_who_may_not_post_is_sent_to_sign_in_by_sell(): void
	{
		// Arrange
		config(['settings.listing_form.guest_can_submit_listings' => '0']);

		// Act
		$appbar = $this->appbar($this->get('/us/search')->assertOk()->getContent());

		// Assert: a real page, not the sign-in pop-up (it is not on every page)
		$this->assertStringContainsString('href="' . route('auth.login.showForm') . '" class="adn-appbar-link adn-appbar-sell"', $appbar);
		$this->assertStringNotContainsString('#quickLogin', $appbar);
	}

	public function test_a_member_goes_to_saved_listings_and_the_account(): void
	{
		// Arrange
		$member = $this->stockMember(self::MEMBER_ID);

		// Act
		$appbar = $this->appbar($this->actingAs($member)->get('/')->assertOk()->getContent());

		// Assert
		$this->assertStringContainsString('href="' . route('account.savedListings') . '"', $appbar);
		$this->assertStringContainsString('href="' . route('account.overview') . '"', $appbar);
	}

	public function test_the_saved_listings_page_marks_saved_only(): void
	{
		// Arrange
		$member = $this->stockMember(self::MEMBER_ID);

		// Act
		$appbar = $this->appbar($this->actingAs($member)->get(route('account.savedListings'))->assertOk()->getContent());

		// Assert
		$this->assertSame(1, substr_count($appbar, 'is-active'));
		$this->assertStringContainsString('bi-heart-fill', $appbar);
		$this->assertStringNotContainsString('bi-person-fill', $appbar);
	}

	public function test_the_search_page_marks_search(): void
	{
		// Act
		$appbar = $this->appbar($this->get('/us/search')->assertOk()->getContent());

		// Assert
		$this->assertMatchesRegularExpression('/is-active"\s+aria-current="page"[^>]*>\s*<span class="adn-appbar-icon"[^>]*>\s*<i class="bi bi-search"/', $appbar);
	}

	public function test_the_listing_form_keeps_its_own_bottom_bar(): void
	{
		// Arrange
		$member = $this->stockMember(self::MEMBER_ID);

		// Act
		$html = $this->actingAs($member)->get('/posts/create')->assertOk()->getContent();

		// Assert
		$this->assertStringNotContainsString('data-adn-appbar', $html);
		$this->assertDoesNotMatchRegularExpression('/<body[^>]*class="[^"]*has-appbar/', $html);
	}

	private function accountMenu(string $html): string
	{
		$start = strpos($html, '<div class="dropdown adn-mhead-account');
		$this->assertNotFalse($start);

		return substr($html, $start, strpos($html, 'class="navbar-toggler', $start) - $start);
	}

	private function appbar(string $html): string
	{
		$this->assertMatchesRegularExpression('/<nav class="adn-appbar[^"]*"[^>]*data-adn-appbar>(.*?)<\/nav>/s', $html);
		preg_match('/<nav class="adn-appbar[^"]*"[^>]*data-adn-appbar>(.*?)<\/nav>/s', $html, $match);

		return $match[1];
	}
}
