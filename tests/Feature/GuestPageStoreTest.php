<?php

namespace Tests\Feature;

use App\Http\Middleware\GuestPageCache;
use App\Services\GuestPageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The server's own copy of guest pages (adniro): a page built for one guest is handed to the next ones for
 * ten minutes without being built again, and never to anyone who is not a plain guest.
 */
class GuestPageStoreTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	protected function setUp(): void
	{
		parent::setUp();
		$_SERVER['GUEST_PAGE_CACHE'] = $_ENV['GUEST_PAGE_CACHE'] = 'true';
		$this->seedStockData();
		$this->stockListing('Grey corner sofa', ['user_id' => $this->stockMember(71)->id]);
	}

	protected function tearDown(): void
	{
		unset($_SERVER['GUEST_PAGE_CACHE'], $_ENV['GUEST_PAGE_CACHE']);
		parent::tearDown();
	}

	public function test_the_second_guest_gets_the_kept_page_without_it_being_built_again(): void
	{
		// Arrange: the first guest's page is built and kept
		$first = $this->get('/us/search')->assertOk()->assertSee('Grey corner sofa');
		$first->assertHeader('X-Guest-Cache', 'cacheable')->assertHeaderMissing('X-Guest-Store');
		$this->assertStringContainsString('s-maxage=' . GuestPageCache::EDGE_TTL, $first->headers->get('Cache-Control'));

		// Act: the listing is renamed behind the store's back, and four minutes pass
		DB::table('posts')->update(['title' => 'Blue armchair']);
		Cache::forget('unused'); // the store itself is left alone
		$this->travel(4)->minutes();
		$second = $this->get('/us/search');

		// Assert: the same page, marked as kept, and the CDN may keep it only for the six minutes left
		$second->assertOk()->assertHeader('X-Guest-Store', 'hit')->assertHeader('X-Guest-Cache', 'cacheable');
		$second->assertSee('Grey corner sofa')->assertDontSee('Blue armchair');
		$this->assertSame($first->getContent(), $second->getContent());
		$this->assertSame($first->headers->get('ETag'), $second->headers->get('ETag'));
		$this->assertStringContainsString('s-maxage=360', $second->headers->get('Cache-Control'));
		$this->assertSame([], $second->headers->getCookies());
	}

	public function test_a_kept_page_is_built_again_after_ten_minutes(): void
	{
		// Arrange
		$this->get('/us/search')->assertOk();
		DB::table('posts')->update(['title' => 'Blue armchair']);

		// Act
		$this->travel(11)->minutes();
		$later = $this->get('/us/search');

		// Assert
		$later->assertOk()->assertHeaderMissing('X-Guest-Store')->assertSee('Blue armchair');
	}

	public function test_a_browser_that_already_has_the_page_is_told_it_has_not_changed(): void
	{
		// Arrange
		$etag = $this->get('/us/search')->headers->get('ETag');

		// Act
		$again = $this->get('/us/search', ['If-None-Match' => $etag]);

		// Assert
		$again->assertStatus(304)->assertHeader('X-Guest-Store', 'hit');
		$this->assertSame('', (string)$again->getContent());
	}

	public function test_a_member_or_a_visitor_with_a_session_never_gets_or_leaves_a_kept_page(): void
	{
		// Arrange: a member's page is built first; nothing may be kept from it
		$member = $this->stockMember(72);
		$this->actingAs($member)->get('/us/search')->assertOk()->assertHeaderMissing('X-Guest-Cache');
		auth()->logout();

		// Act: a guest, then a visitor carrying a session cookie, then the member again
		$guest = $this->get('/us/search');
		$withSession = $this->withUnencryptedCookie(config('session.cookie'), 'abc')->get('/us/search');
		$memberAgain = $this->actingAs($member)->get('/us/search');

		// Assert
		$guest->assertOk()->assertHeaderMissing('X-Guest-Store'); // the member's page was not kept
		$withSession->assertOk()->assertHeaderMissing('X-Guest-Store')->assertHeaderMissing('X-Guest-Cache');
		$memberAgain->assertOk()->assertHeaderMissing('X-Guest-Store')->assertHeaderMissing('X-Guest-Cache');
	}

	public function test_only_plain_addresses_are_kept(): void
	{
		// Act: a search with words is one of too many to keep; "page" is fine
		$this->get('/us/search?q=sofa')->assertOk();
		$words = $this->get('/us/search?q=sofa');
		$this->get('/us/search?page=1')->assertOk();
		$paged = $this->get('/us/search?page=1');
		$otherPage = $this->get('/us/search?page=2');

		// Assert
		$words->assertOk()->assertHeader('X-Guest-Cache', 'cacheable')->assertHeaderMissing('X-Guest-Store');
		$paged->assertHeader('X-Guest-Store', 'hit');
		$otherPage->assertHeaderMissing('X-Guest-Store'); // its own page, not page 1's
	}

	public function test_a_visitor_who_chose_a_language_is_not_a_plain_guest(): void
	{
		// Arrange: a kept page exists
		$this->get('/us/search')->assertOk();

		// Act: a visitor without a session, but with the cookie of the language they picked
		$withLanguage = $this->withUnencryptedCookie('langCode', 'fr')->get('/us/search');

		// Assert: their page is built for them, and is nobody else's
		$withLanguage->assertOk()->assertHeaderMissing('X-Guest-Store')->assertHeaderMissing('X-Guest-Cache');
	}

	public function test_a_member_set_on_the_request_without_a_cookie_still_gets_their_own_page(): void
	{
		// Arrange: a guest's page is kept
		$this->get('/us/search')->assertOk();

		// Act: the member has no session cookie here (only tests can do this)
		$forMember = $this->actingAs($this->stockMember(73))->get('/us/search');

		// Assert
		$forMember->assertOk()->assertHeaderMissing('X-Guest-Store')->assertSee('Member 73');
	}

	public function test_odd_page_values_and_head_requests_get_no_copy(): void
	{
		// Arrange
		$covers = fn (string $url, string $method = 'GET') => GuestPageStore::covers(\Illuminate\Http\Request::create($url, $method));

		// Act & Assert
		$this->assertTrue($covers('https://www.adniro.test/us/search?page=12'));
		foreach (['page[]=1', 'page=0', 'page=-1', 'page=1e3', 'page=99999', 'page=abc', 'page=1&q=x'] as $query) {
			$this->assertFalse($covers('https://www.adniro.test/us/search?' . $query), $query);
		}
		$this->get('/us/search')->assertOk();
		$this->assertNull(GuestPageStore::get(\Illuminate\Http\Request::create('http://localhost/us/search', 'HEAD')));
	}

	public function test_on_a_live_site_a_made_up_host_name_gets_no_copy(): void
	{
		// Arrange
		config(['app.url' => 'https://www.adniro.test']);
		$this->app->detectEnvironment(fn () => 'production');

		// Act & Assert
		$this->assertTrue(GuestPageStore::covers(\Illuminate\Http\Request::create('https://adniro.test/us/search')));
		$this->assertFalse(GuestPageStore::covers(\Illuminate\Http\Request::create('https://made-up.example/us/search')));
	}

	public function test_pages_that_depend_on_the_visitors_country_or_language_are_not_kept(): void
	{
		// Arrange
		$request = \Illuminate\Http\Request::create('https://www.adniro.test/us/search');
		$this->assertTrue(GuestPageStore::covers($request));

		// Act & Assert
		config(['settings.localization.geoip_activation' => '1']);
		$this->assertFalse(GuestPageStore::covers($request));
		config(['settings.localization.geoip_activation' => '0', 'settings.localization.auto_detect_language' => 'from_browser']);
		$this->assertFalse(GuestPageStore::covers($request));
	}

	public function test_with_the_guest_cache_turned_off_nothing_is_kept(): void
	{
		// Arrange
		$_SERVER['GUEST_PAGE_CACHE'] = $_ENV['GUEST_PAGE_CACHE'] = 'false';

		// Act
		$this->get('/us/search')->assertOk();
		$second = $this->get('/us/search');

		// Assert
		$second->assertOk()->assertHeaderMissing('X-Guest-Store')->assertHeaderMissing('X-Guest-Cache');
	}

	public function test_a_listing_page_is_kept_too_and_a_page_that_is_not_found_is_not(): void
	{
		// Arrange
		$url = urlGen()->post(\App\Models\Post::query()->firstOrFail());

		// Act
		$this->get($url)->assertOk();
		$listingAgain = $this->get($url);
		$this->get('/page/nothing-here')->assertNotFound();
		$missingAgain = $this->get('/page/nothing-here');

		// Assert
		$listingAgain->assertOk()->assertHeader('X-Guest-Store', 'hit')->assertSee('Grey corner sofa');
		$missingAgain->assertNotFound()->assertHeaderMissing('X-Guest-Store');
	}
}
