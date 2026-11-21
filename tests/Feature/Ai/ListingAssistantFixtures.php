<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\ListingAssistant\ListingAssistantService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * Shared set-up for the listing assistant tests (adniro:ai): a small catalog, two cities,
 * a fake AI client and the listing assistant switched on.
 */

trait ListingAssistantFixtures
{
	protected FakeAiClient $ai;
	protected AiModel $model;
	
	protected function setUpListingAssistant(): void
	{
		
		// The test database has no countries or languages: the app's localization middleware is
		// left out and the country is set below instead
		$this->withoutMiddleware([
			\App\Http\Middleware\VerifyCsrfToken::class,
			\App\Http\Middleware\ValidateCsrfToken::class,
			\App\Http\Middleware\RequirementsChecker::class,
			\App\Http\Middleware\GetLocalization::class,
			\App\Http\Middleware\SetBrowserLocale::class,
			\App\Http\Middleware\SetCountryLocale::class,
			\App\Http\Middleware\SetDefaultLocale::class,
			\App\Http\Middleware\TipsMessages::class,
			\App\Http\Middleware\LazyLoading::class,
			\App\Http\Middleware\HtmlMinify::class,
		]);
		config([
			'country.code'                                   => 'US',
			'country.name'                                   => 'United States',
			'currency.code'                                  => 'USD',
			'app.timezone'                                   => 'UTC',
			'settings.listing_form.publication_form_type'    => 'multi-steps-form',
			'settings.listing_form.guest_can_submit_listings' => '1',
			'settings.listing_form.wysiwyg_editor'           => 'tinymce',
			'settings.listing_form.show_listing_type'        => '0',
			'settings.listing_form.price_mandatory'          => '0',
		]);
		Cache::flush();
		
		$this->ai = new FakeAiClient();
		$this->app->instance(AiClientContract::class, $this->ai);
		$this->app->forgetInstance(ListingAssistantService::class);
		
		$provider = AiProvider::query()->create(['name' => 'Test', 'driver' => 'anthropic', 'api_key' => 'k', 'active' => true]);
		$this->model = AiModel::query()->forceCreate([
			'ai_provider_id' => $provider->id, 'model_id' => 'fake-model', 'supports_vision' => 1,
			'supports_structured_output' => 1, 'active' => 1, 'last_test_ok' => 1,
		]);
		$this->setAi();
		$this->seedCatalog();
	}

	protected function draftAnswer(): array
	{
		return [
			'is_listable'     => true,
			'unusable_reason' => '',
			'items_detected'  => 1,
			'title'           => ['value' => 'Apple iPhone 13 128GB Blue', 'confidence' => 'high'],
			'description'     => ['value' => "Blue iPhone 13 with box.\nSmall scratch on the back. Call +1 555 123 4567", 'confidence' => 'medium'],
			'category'        => ['id' => 2, 'confidence' => 'high', 'alternatives' => [3]],
			'post_type_id'    => 1,
			'price'           => ['stated' => true, 'amount' => 420, 'currency' => 'USD'],
			'location'        => '',
			'tags'            => ['iphone', '#apple'],
			'custom_fields'   => [
				['field_id' => 10, 'value' => '', 'option_ids' => [101], 'confidence' => 'high'],
				['field_id' => 11, 'value' => '128 GB', 'option_ids' => [], 'confidence' => 'medium'],
				['field_id' => 12, 'value' => '', 'option_ids' => [121, 999], 'confidence' => 'high'],
				['field_id' => 15, 'value' => 'https://example.test', 'option_ids' => [], 'confidence' => 'high'],
				['field_id' => 99, 'value' => 'x', 'option_ids' => [], 'confidence' => 'high'],
			],
			'policy_flags'    => [],
		];
	}

	protected function setAi(array $overrides = []): void
	{
		$current = (array)(Setting::query()->where('name', 'ai')->value('field_values') ?? []);
		if (is_string($current)) {
			$current = json_decode($current, true) ?: [];
		}
		Setting::query()->updateOrCreate(['name' => 'ai'], ['label' => 'AI', 'active' => 1, 'field_values' => array_merge([
			'enabled'            => '1',
			'listing_assistant_enabled'  => '1',
			'listing_assistant_model_id' => (string)$this->model->id,
		], $current, $overrides)]);
	}

	protected function seedCatalog(): void
	{
		DB::table('categories')->insert([
			['id' => 1, 'parent_id' => null, 'name' => json_encode(['en' => 'Phones']), 'slug' => 'phones', 'lft' => 1, 'rgt' => 4, 'depth' => 0, 'type' => 'classified', 'active' => 1],
			['id' => 2, 'parent_id' => 1, 'name' => json_encode(['en' => 'Mobile phones']), 'slug' => 'mobile-phones', 'lft' => 2, 'rgt' => 3, 'depth' => 1, 'type' => 'classified', 'active' => 1],
			['id' => 4, 'parent_id' => null, 'name' => json_encode(['en' => 'Computers']), 'slug' => 'computers', 'lft' => 5, 'rgt' => 8, 'depth' => 0, 'type' => 'classified', 'active' => 1],
			['id' => 3, 'parent_id' => 4, 'name' => json_encode(['en' => 'Laptops']), 'slug' => 'laptops', 'lft' => 6, 'rgt' => 7, 'depth' => 1, 'type' => 'classified', 'active' => 1],
		]);
		$field = fn ($id, $name, $type, $required = 0) => ['id' => $id, 'belongs_to' => 'post', 'name' => json_encode(['en' => $name]), 'type' => $type, 'required' => $required, 'active' => 1];
		DB::table('fields')->insert([
			$field(10, 'Brand', 'select', 1), $field(11, 'Storage', 'number'), $field(12, 'Extras', 'checkbox_multiple'),
			$field(13, 'Invoice', 'file'), $field(14, 'Colour', 'text'), $field(15, 'Website', 'url'), $field(99, 'Other', 'text'),
		]);
		DB::table('fields_options')->insert([
			['id' => 101, 'field_id' => 10, 'value' => json_encode(['en' => 'Apple'])],
			['id' => 102, 'field_id' => 10, 'value' => json_encode(['en' => 'Samsung'])],
			['id' => 121, 'field_id' => 12, 'value' => json_encode(['en' => 'Box'])],
			['id' => 122, 'field_id' => 12, 'value' => json_encode(['en' => 'Charger'])],
		]);
		foreach ([10, 11, 12, 13, 14, 15] as $i => $fieldId) {
			DB::table('category_field')->insert(['category_id' => 1, 'field_id' => $fieldId, 'disabled_in_subcategories' => 0, 'lft' => $i * 2 + 1, 'rgt' => $i * 2 + 2]);
		}
		DB::table('cities')->insert([
			['id' => 600, 'country_code' => 'US', 'name' => json_encode(['en' => 'Springfield']), 'latitude' => 39.80, 'longitude' => -89.64, 'population' => 100000, 'active' => 1],
			['id' => 700, 'country_code' => 'US', 'name' => json_encode(['en' => 'Chicago']), 'latitude' => 41.85, 'longitude' => -87.65, 'population' => 2700000, 'active' => 1],
		]);
	}

	protected function user(int $id): User
	{
		return User::query()->forceCreate([
			'id' => $id, 'name' => "User {$id}", 'email' => "user{$id}@example.test", 'auth_field' => 'email',
			'password' => bcrypt(str()->random(32)), 'email_verified_at' => now(), 'phone_verified_at' => now(),
		]);
	}

	protected function admin(): User
	{
		foreach (array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()) as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
		$admin = $this->user(1);
		$admin->givePermissionTo(array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()));
		
		return $admin;
	}
}
