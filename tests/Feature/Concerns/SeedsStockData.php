<?php

namespace Tests\Feature\Concerns;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The data a real install has (countries, languages, settings, categories, pages...), from the
 * stock seeders, for tests that render whole pages. Use with RefreshDatabase.
 */
trait SeedsStockData
{
	/**
	 * The stock seeders, called one by one: DatabaseSeeder truncates the tables first, which MySQL
	 * commits, and its rows would then leak into later tests. These only insert, inside the test's
	 * transaction.
	 */
	private static array $stockSeeders = [
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
	private static array $stockTables = [
		'menus', 'menu_items', 'languages', 'advertising', 'categories', 'currencies', 'fields', 'fields_options',
		'sections', 'packages', 'pages', 'payment_methods', 'report_types', 'settings', 'category_field', 'countries',
	];

	protected function seedStockData(): void
	{
		// DELETE (not TRUNCATE) stays inside the test's transaction
		Schema::disableForeignKeyConstraints();
		foreach (self::$stockTables as $table) {
			if (Schema::hasTable($table)) {
				DB::table($table)->delete();
			}
		}
		Schema::enableForeignKeyConstraints();
		$this->seed(self::$stockSeeders);

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

	protected function stockMember(int $id): User
	{
		return User::query()->forceCreate([
			'id' => $id, 'name' => "Member {$id}", 'email' => "member{$id}@example.test", 'auth_field' => 'email',
			'password' => bcrypt(str()->random(32)), 'email_verified_at' => now(), 'phone_verified_at' => now(),
			'country_code' => 'US',
		]);
	}

	/**
	 * A live, approved listing in a US city.
	 */
	protected function stockListing(string $title, array $overrides = []): Post
	{
		$categoryId = (int)DB::table('categories')->whereNotNull('parent_id')->where('active', 1)->value('id');
		$cityId = DB::table('cities')->insertGetId([
			'country_code' => 'US', 'name' => json_encode(['en' => 'Springfield']), 'latitude' => 39.8, 'longitude' => -89.6,
			'population' => 100000, 'active' => 1, 'created_at' => now(), 'updated_at' => now(),
		]);
		$id = DB::table('posts')->insertGetId(array_merge([
			'country_code' => 'US', 'category_id' => $categoryId, 'city_id' => $cityId, 'post_type_id' => 1,
			'title' => $title, 'description' => 'A comfortable sofa in very good condition.', 'price' => 150,
			'currency_code' => 'USD', 'contact_name' => 'Sam', 'email' => 'sam@example.test', 'auth_field' => 'email',
			'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(), 'moderation_status' => 'approved',
			'created_at' => now(), 'updated_at' => now(),
		], $overrides));

		return Post::query()->withoutGlobalScopes()->find($id);
	}
}
