<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Smoke tests for the main public pages (adniro, phase 7): they must still render after a
 * LaraClassifier upgrade or a dependency update. The stock seeders give the countries,
 * languages, settings, categories and pages a real install has.
 */

class PublicPagesSmokeTest extends TestCase
{
	use RefreshDatabase;

	/**
	 * The stock seeders, called one by one: DatabaseSeeder truncates the tables first, which MySQL
	 * commits, and its rows would then leak into later tests. These only insert, inside the test's
	 * transaction.
	 */
	private const SEEDERS = [
		\Database\Seeders\MenuSeeder::class,
		\Database\Seeders\LanguageSeeder::class,
		\Database\Seeders\AdvertisingSeeder::class,
		\Database\Seeders\CategorySeeder::class,
		\Database\Seeders\CurrencySeeder::class,
		\Database\Seeders\FieldSeeder::class,
		\Database\Seeders\SectionSeeder::class,
		\Database\Seeders\PackageSeeder::class,
		\Database\Seeders\PageSeeder::class,
		\Database\Seeders\PaymentMethodSeeder::class,
		\Database\Seeders\MenuItemSeeder::class,
		\Database\Seeders\ReportTypeSeeder::class,
		\Database\Seeders\SettingSeeder::class,
		\Database\Seeders\CategoryFieldSeeder::class,
		\Database\Seeders\CountrySeeder::class,
	];

	/** Tables the seeders fill; some migrations already add rows to them. */
	private const SEEDED_TABLES = [
		'menus', 'menu_items', 'languages', 'advertising', 'categories', 'currencies', 'fields', 'fields_options',
		'sections', 'packages', 'pages', 'payment_methods', 'report_types', 'settings', 'category_field', 'countries',
	];

	protected function setUp(): void
	{
		parent::setUp();

		// DELETE (not TRUNCATE) stays inside the test's transaction
		\Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
		foreach (self::SEEDED_TABLES as $table) {
			if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
				DB::table($table)->delete();
			}
		}
		\Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
		$this->seed(self::SEEDERS);

		// Settings are read when the app boots, before seeding: load them now, with the US as the
		// active default country, as on the live site
		DB::table('countries')->where('code', 'US')->update(['active' => 1]);
		foreach (DB::table('settings')->get(['name', 'field_values']) as $setting) {
			foreach ((array)json_decode((string)$setting->field_values, true) as $key => $value) {
				config(['settings.' . $setting->name . '.' . $key => $value]);
			}
		}
		config([
			'settings.localization.default_country_code' => 'US',
			'settings.localization.geoip_activation'     => '0',
			'settings.app.name'                          => 'Adniro',
		]);
	}

	public function test_the_home_page_renders_with_its_structured_data(): void
	{
		// Act
		$response = $this->get('/');

		// Assert
		$response->assertOk()->assertSee('<h1', false)->assertSee('"@type":"Organization"', false);
	}

	public function test_a_listing_page_renders(): void
	{
		// Arrange
		$post = $this->listing('Grey corner sofa');

		// Act & Assert
		$this->get(urlGen()->post($post))
			->assertOk()
			->assertSee('Grey corner sofa')
			->assertSee('application/ld+json', false);
	}

	public function test_search_finds_a_listing(): void
	{
		// Arrange
		$this->listing('Grey corner sofa');

		// Act & Assert
		$this->get('/us/search?q=sofa')->assertOk()->assertSee('Grey corner sofa');
	}

	public function test_a_page_and_the_listing_form_render(): void
	{
		// Arrange: guests may post, as on the live site
		config(['settings.listing_form.guest_can_submit_listings' => '1']);
		$page = DB::table('pages')->where('active', 1)->value('slug');

		// Act & Assert
		$this->get('/page/' . $page)->assertOk();
		$this->get('/posts/create')->assertOk()->assertSee('payableForm', false);
	}

	public function test_a_missing_page_answers_404_and_the_sitemap_is_xml(): void
	{
		// Act & Assert
		$this->get('/no-such-page-' . uniqid())->assertNotFound();
		$this->get('/us/sitemaps.xml')->assertOk()->assertSee('<sitemapindex', false);
	}

	private function listing(string $title): Post
	{
		$categoryId = (int)DB::table('categories')->whereNotNull('parent_id')->where('active', 1)->value('id');
		$cityId = DB::table('cities')->insertGetId([
			'country_code' => 'US', 'name' => json_encode(['en' => 'Springfield']), 'latitude' => 39.8, 'longitude' => -89.6,
			'population' => 100000, 'active' => 1, 'created_at' => now(), 'updated_at' => now(),
		]);
		$id = DB::table('posts')->insertGetId([
			'country_code' => 'US', 'category_id' => $categoryId, 'city_id' => $cityId, 'post_type_id' => 1,
			'title' => $title, 'description' => 'A comfortable sofa in very good condition.', 'price' => 150,
			'currency_code' => 'USD', 'contact_name' => 'Sam', 'email' => 'sam@example.test', 'auth_field' => 'email',
			'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(), 'moderation_status' => 'approved',
			'created_at' => now(), 'updated_at' => now(),
		]);

		return Post::query()->withoutGlobalScopes()->find($id);
	}
}
