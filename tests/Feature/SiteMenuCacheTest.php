<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The site's menus (header, footer) are kept in the cache as the views need them (adniro): the same menu as
 * building it from its entries each time, never one visitor's menu shown to another, and forgotten when an
 * entry changes.
 */
class SiteMenuCacheTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const PARAMS = ['embed' => 'menu,parent,ancestors,children', 'sort' => '-lft', 'page' => 1];

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
	}

	public function test_the_cached_menu_is_the_menu_built_from_its_entries(): void
	{
		// Arrange
		$service = new MenuItemService();

		// Act & Assert: the first call builds it, the second reads it back
		foreach (['header', 'footer'] as $location) {
			$built = getServiceData($service->getEntries(location: $location, params: self::PARAMS));
			$this->assertNotEmpty(data_get($built, 'result.data'), $location);
			$this->assertSame($built, $service->getSiteMenu($location, self::PARAMS), $location);
			$this->assertSame($built, $service->getSiteMenu($location, self::PARAMS), $location . ' again');
		}
	}

	public function test_a_guest_and_a_member_never_get_each_others_menu(): void
	{
		// Arrange
		$service = new MenuItemService();
		$member = $this->stockMember(71);
		$other = $this->stockMember(72);
		$shown = fn (string $location) => $this->shownLabels($service->getSiteMenu($location, self::PARAMS));

		// Act: the guest's menus are cached first, then two members look, then a guest again
		$guestFooter = $shown('footer');
		$guestHeader = $shown('header');
		$this->actingAs($member);
		$memberFooter = $shown('footer');
		$memberHeader = $shown('header');
		$this->actingAs($other);
		$otherHeader = $shown('header');
		auth()->logout();
		$guestFooterAgain = $shown('footer');
		$guestHeaderAgain = $shown('header');

		// Assert: "Sign Up" is for guests, "My listings" for members; each member sees their own name
		foreach ([$guestFooter, $guestFooterAgain] as $labels) {
			$this->assertContains('Sign Up', $labels);
			$this->assertNotContains('My listings', $labels);
		}
		$this->assertContains('My listings', $memberFooter);
		$this->assertNotContains('Sign Up', $memberFooter);
		$this->assertContains('Member 71', $memberHeader);
		$this->assertContains('Member 72', $otherHeader);
		$this->assertNotContains('Member 71', $otherHeader);
		foreach ([$guestHeader, $guestHeaderAgain] as $labels) {
			$this->assertSame([], array_values(array_filter($labels, fn ($label) => str_contains($label, 'Member 7'))));
			$this->assertContains('Sign Up', $labels);
		}
	}

	public function test_a_members_menu_with_a_personal_entry_is_built_each_time(): void
	{
		// Arrange: the footer has nothing personal; the header shows the member's name and unread chats
		$service = new MenuItemService();
		$kind = fn (string $location) => (fn () => $this->visitorKind($this->cachedEntries($location, null, SiteMenuCacheTest::PARAMS)))->call($service);

		// Act & Assert
		$this->assertSame('guest', $kind('header'));
		$this->actingAs($this->stockMember(71));
		$this->assertNull($kind('header'));
		$this->assertSame('member', $kind('footer'));

		// A footer entry for one role only makes the footer personal too
		$entry = MenuItem::query()->whereHas('menu', fn ($menu) => $menu->forLocation('footer'))->whereNotNull('parent_id')->firstOrFail();
		$entry->update(['roles' => ['super-admin']]);
		$this->assertNull($kind('footer'));

		// So does a link to where the member posts a listing: it depends on the member's package
		$entry->update(['roles' => null, 'route_name' => 'listing.create.ms.showForm']);
		$this->assertNull($kind('footer'));
		$entry->update(['route_name' => 'contact.showForm']);
		$this->assertSame('member', $kind('footer'));
	}

	public function test_a_setting_that_changes_the_menu_shows_on_the_next_page(): void
	{
		// Arrange: a guest's header, kept with the sign-in link going to the sign-in page
		$service = new MenuItemService();
		$signInLink = function () use ($service): ?string {
			foreach ((array)data_get($service->getSiteMenu('header', self::PARAMS), 'result.data') as $entry) {
				foreach ((array)data_get($entry, 'children') as $child) {
					if (($child['route_name'] ?? null) === 'auth.login.showForm') {
						return $child['full_url'];
					}
				}
			}

			return null;
		};
		config(['settings.auth.open_login_in_modal' => '0']);
		$before = $signInLink();

		// Act: the admin turns the sign-in pop-up on
		config(['settings.auth.open_login_in_modal' => '1']);
		$after = $signInLink();

		// Assert: not the kept menu
		$this->assertSame(route('auth.login.showForm'), $before);
		$this->assertSame('#quickLogin', $after);
	}

	public function test_on_a_live_site_only_the_sites_own_address_gets_a_kept_menu(): void
	{
		// Arrange
		$service = new MenuItemService();
		$isKept = fn () => (fn () => $this->isOnOwnHost())->call($service);
		config(['app.url' => 'https://www.adniro.test']);
		$from = fn (string $host) => $this->app->instance('request', \Illuminate\Http\Request::create('https://' . $host . '/'));

		// Act & Assert: anywhere but live, any address
		$from('made-up.example');
		$this->assertTrue($isKept());

		$this->app->detectEnvironment(fn () => 'production');
		$this->assertFalse($isKept());
		$from('www.adniro.test');
		$this->assertTrue($isKept());
		$from('adniro.test');
		$this->assertTrue($isKept());
	}

	public function test_the_entry_of_the_page_being_looked_at_is_marked_on_that_page_only(): void
	{
		// Arrange
		DB::table('pages')->where('slug', 'faq')->update(['active' => 1]);
		$service = new MenuItemService();
		$faq = MenuItem::query()->where('route_name', 'page.faq')->firstOrFail();
		$current = function (string $url) use ($service, $faq): array {
			$this->get($url); // the page itself fills the cache for its own case
			$footer = $service->getSiteMenu('footer', self::PARAMS);
			$marked = [];
			foreach ((array)data_get($footer, 'result.data') as $entry) {
				foreach ((array)data_get($entry, 'children') as $child) {
					if (!empty($child['is_current_page'])) {
						$marked[] = (int)$child['id'];
					}
				}
			}

			return $marked;
		};

		// Act & Assert: the answer for one page is never served on another
		$this->assertSame([], $current('/us/search'));
		$this->assertSame([(int)$faq->id], $current('/page/faq'));
		$this->assertSame([], $current('/us/search'));
	}

	public function test_a_changed_entry_shows_at_once(): void
	{
		// Arrange: the guest's header is cached with "Sign Up"
		$service = new MenuItemService();
		$this->assertContains('Sign Up', $this->shownLabels($service->getSiteMenu('header', self::PARAMS)));
		$entry = MenuItem::query()->where('route_name', 'auth.register.showForm')
			->whereHas('menu', fn ($menu) => $menu->forLocation('header'))->firstOrFail();

		// Act: saving an entry clears the menu entries' cache group
		$entry->update(['label' => 'Join Adniro']);
		$after = $this->shownLabels($service->getSiteMenu('header', self::PARAMS));

		// Assert: the finished menus are in that group, so they went with it
		$this->assertContains('Join Adniro', $after);
		$this->assertNotContains('Sign Up', $after);
	}

	public function test_the_pages_still_show_their_menus(): void
	{
		// Act
		$html = $this->get('/us/search')->assertOk()->getContent();

		// Assert: the header's links for a guest, built through the cache
		$this->assertStringContainsString(route('auth.register.showForm'), $html);
		$this->assertStringContainsString(route('auth.login.showForm'), $html);
	}

	/** The labels a visitor is shown: the entries and their children that say they should be displayed. */
	private function shownLabels(array $menu): array
	{
		$labels = [];
		foreach ((array)data_get($menu, 'result.data') as $entry) {
			foreach (array_merge([$entry], (array)data_get($entry, 'children')) as $item) {
				if (!empty($item['should_display'])) {
					$labels[] = trim(strip_tags((string)$item['formatted_label']));
				}
			}
		}

		return $labels;
	}
}
