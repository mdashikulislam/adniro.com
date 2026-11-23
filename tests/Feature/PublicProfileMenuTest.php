<?php

namespace Tests\Feature;

use App\Services\MenuItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * "View public profile" in the account menu (adniro): at the top of the member's menu, in the header and in
 * the phone menu, opening their own public profile in a new tab.
 */
class PublicProfileMenuTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
	}

	public function test_the_link_takes_a_member_to_their_own_public_profile(): void
	{
		// Arrange
		$member = $this->stockMember(71);
		DB::table('users')->where('id', 71)->update(['username' => 'greysofa']);

		// Act & Assert
		$this->actingAs($member->fresh())->get('/account/public-profile')->assertRedirect(urlGen()->user(['username' => 'greysofa']));
	}

	public function test_a_guest_is_asked_to_sign_in(): void
	{
		$response = $this->get('/account/public-profile');

		$response->assertRedirect();
		$this->assertStringNotContainsString('greysofa', (string)$response->headers->get('Location'));
	}

	public function test_it_is_the_first_entry_of_the_members_header_menu_and_opens_a_new_tab(): void
	{
		// Arrange
		$this->actingAs($this->stockMember(71));

		// Act
		$header = (new MenuItemService())->getSiteMenu('header', ['embed' => 'menu,parent,ancestors,children', 'sort' => '-lft', 'page' => 1]);
		$memberEntry = collect(data_get($header, 'result.data'))->first(fn ($entry) => collect($entry['children'] ?? [])->contains('route_name', 'account.listings.online'));
		$first = $memberEntry['children'][0];

		// Assert
		$this->assertSame('account.public-profile', $first['route_name']);
		$this->assertSame('View public profile', trim(strip_tags((string)$first['formatted_label'])));
		$this->assertSame(route('account.public-profile'), $first['full_url']);
		$this->assertStringContainsString('target="_blank"', (string)$first['label_html']);
		$this->assertTrue($first['should_display']);
	}

	public function test_the_phone_menu_has_it_for_members_only(): void
	{
		// Act
		$forGuest = $this->get('/us/search')->assertOk()->getContent();
		$forMember = $this->actingAs($this->stockMember(71))->get('/us/search')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('href="' . route('account.public-profile') . '" target="_blank"', $forMember);
		$this->assertStringContainsString('View public profile', $forMember);
		$this->assertStringNotContainsString(route('account.public-profile'), $forGuest);
	}
}
