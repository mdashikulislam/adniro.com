<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\Permission;
use App\Models\User;
use App\Services\Ai\Agents\StructuredTaskAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Admin > AI Providers and AI Models, exercised over HTTP as a super admin.
 */

class AiAdminScreensTest extends TestCase
{
	use RefreshDatabase;
	
	private User $admin;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		$this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
		
		foreach (array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()) as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
		
		$this->admin = User::query()->forceCreate([
			'name'              => 'Owner',
			'email'             => 'owner@example.test',
			'auth_field'        => 'email',
			'password'          => bcrypt(str()->random(32)),
			'email_verified_at' => now(),
			'phone_verified_at' => now(),
		]);
		$this->admin->givePermissionTo(array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()));
	}
	
	public function test_the_provider_list_and_forms_open(): void
	{
		AiProvider::query()->create(['name' => 'Main Anthropic', 'driver' => 'anthropic', 'api_key' => 'secret-key-9876', 'active' => true]);
		
		$this->actingAs($this->admin)->get('/admin/ai_providers')->assertOk();
		$rows = $this->actingAs($this->admin)->post('/admin/ai_providers/search', ['draw' => 1, 'start' => 0, 'length' => 10]);
		$rows->assertOk();
		$html = $this->rowsHtml($rows);
		$this->assertStringContainsString('Main Anthropic', $html);
		$this->assertStringContainsString('Anthropic (Claude)', $html);
		$this->assertStringContainsString('secret-key-9876', $html, 'admins see the full key');
		$this->assertStringContainsString('Test connection', $html);
		$this->actingAs($this->admin)->get('/admin/ai_providers/create')->assertOk()->assertSee('Anthropic (Claude)');
		
		$edit = $this->actingAs($this->admin)->get('/admin/ai_providers/' . AiProvider::query()->value('id') . '/edit');
		$edit->assertOk();
		$edit->assertSee('value="secret-key-9876"', false);
	}
	
	public function test_a_provider_is_created_with_its_key_as_plain_text(): void
	{
		$this->actingAs($this->admin)->post('/admin/ai_providers', [
			'name'    => 'OpenAI account',
			'driver'  => 'openai',
			'api_key' => 'sk-test-abcdef123456',
			'active'  => 1,
		])->assertRedirect();
		
		$provider = AiProvider::query()->sole();
		$this->assertSame('sk-test-abcdef123456', $provider->api_key);
		$this->assertSame('sk-test-abcdef123456', DB::table('ai_providers')->value('api_key'));
		$this->assertArrayNotHasKey('api_key', $provider->toArray(), 'never in JSON output');
	}
	
	public function test_the_key_can_be_changed_or_removed_from_the_form(): void
	{
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'api_key' => 'old-key-1111', 'active' => true]);
		$save = fn (string $key) => $this->actingAs($this->admin)->put('/admin/ai_providers/' . $provider->id, [
			'id' => $provider->id, 'name' => 'Main renamed', 'driver' => 'anthropic', 'api_key' => $key, 'active' => 1,
		])->assertRedirect();
		
		$save('  new-key-2222 ');
		$provider->refresh();
		$this->assertSame('Main renamed', $provider->name);
		$this->assertSame('new-key-2222', $provider->api_key);
		
		$save('');
		$this->assertNull($provider->fresh()->readApiKey(), 'an emptied field removes the key; the .env key is used then');
	}
	
	public function test_the_migration_turns_encrypted_keys_into_plain_text(): void
	{
		$readable = AiProvider::query()->create(['name' => 'A', 'driver' => 'anthropic', 'active' => true]);
		$lost = AiProvider::query()->create(['name' => 'B', 'driver' => 'openai', 'active' => true]);
		$plain = AiProvider::query()->create(['name' => 'C', 'driver' => 'openai', 'api_key' => 'plain-3333', 'active' => true]);
		$other = new \Illuminate\Encryption\Encrypter(random_bytes(32), 'AES-256-CBC');
		DB::table('ai_providers')->where('id', $readable->id)->update(['api_key' => \Illuminate\Support\Facades\Crypt::encryptString('readable-1111')]);
		DB::table('ai_providers')->where('id', $lost->id)->update(['api_key' => $other->encryptString('lost-2222')]);
		
		$migration = require database_path('migrations/2026_11_20_001000_store_ai_provider_keys_as_plain_text.php');
		$migration->up();
		
		$this->assertSame('readable-1111', DB::table('ai_providers')->where('id', $readable->id)->value('api_key'));
		$this->assertNull(DB::table('ai_providers')->where('id', $lost->id)->value('api_key'), 'a key nobody can read is cleared');
		$this->assertSame('plain-3333', DB::table('ai_providers')->where('id', $plain->id)->value('api_key'));
	}
	
	public function test_a_custom_url_is_required_for_an_openai_compatible_provider(): void
	{
		$this->actingAs($this->admin)->post('/admin/ai_providers', [
			'name'   => 'Gateway',
			'driver' => 'openai-compatible',
			'active' => 1,
		])->assertSessionHasErrors('base_url');
	}
	
	public function test_models_are_created_listed_and_tested(): void
	{
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'api_key' => 'k-1234', 'active' => true]);
		
		$this->actingAs($this->admin)->post('/admin/ai_models', [
			'ai_provider_id'             => $provider->id,
			'model_id'                   => 'claude-opus-5-5',
			'label'                      => 'Claude Opus 5.5',
			'supports_vision'            => 1,
			'supports_structured_output' => 1,
			'input_price_per_mtok'       => '4',
			'output_price_per_mtok'      => '20',
			'options'                    => '{"output_config": {"effort": "low"}}',
			'active'                     => 1,
		])->assertRedirect();
		
		$model = AiModel::query()->sole();
		$this->assertSame(['output_config' => ['effort' => 'low']], $model->optionsArray());
		
		$this->actingAs($this->admin)->get('/admin/ai_models')->assertOk();
		$rows = $this->actingAs($this->admin)->post('/admin/ai_models/search', ['draw' => 1, 'start' => 0, 'length' => 10]);
		$rows->assertOk();
		$html = $this->rowsHtml($rows);
		$this->assertStringContainsString('Claude Opus 5.5', $html);
		$this->assertStringContainsString('$4 / $20', $html);
		$this->assertStringContainsString('Not tested', $html);
		$this->assertStringContainsString('Test model', $html);
		$this->actingAs($this->admin)->get('/admin/ai_models/' . $model->id . '/edit')->assertOk();
		
		StructuredTaskAgent::fake([['ok' => true, 'answer' => 'pong', 'colour' => 'red']]);
		$this->actingAs($this->admin)->post('/admin/ai_models/' . $model->id . '/test')->assertRedirect();
		
		$this->assertTrue($model->fresh()->last_test_ok);
	}
	
	public function test_invalid_provider_options_are_rejected(): void
	{
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'active' => true]);
		
		$this->actingAs($this->admin)->post('/admin/ai_models', [
			'ai_provider_id' => $provider->id,
			'model_id'       => 'some-model',
			'options'        => '{not json',
		])->assertSessionHasErrors('options');
	}
	
	public function test_changing_a_model_id_clears_its_test_result(): void
	{
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'active' => true]);
		$model = AiModel::query()->create(['ai_provider_id' => $provider->id, 'model_id' => 'old-model', 'active' => true, 'supports_vision' => true]);
		$model->forceFill(['last_test_ok' => true, 'last_tested_at' => now()])->save();
		
		$this->actingAs($this->admin)->put('/admin/ai_models/' . $model->id, [
			'id'                         => $model->id,
			'ai_provider_id'             => $provider->id,
			'model_id'                   => 'new-model',
			'supports_vision'            => 1,
			'supports_structured_output' => 1,
			'active'                     => 1,
		])->assertRedirect();
		
		$this->assertNull($model->fresh()->last_test_ok);
	}
	
	public function test_the_ai_settings_screen_lists_only_tested_models_that_suit_each_feature(): void
	{
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'active' => true]);
		$ready = AiModel::query()->create(['ai_provider_id' => $provider->id, 'model_id' => 'ready-vision-model', 'supports_vision' => true, 'active' => true]);
		$ready->forceFill(['last_test_ok' => true])->save();
		AiModel::query()->create(['ai_provider_id' => $provider->id, 'model_id' => 'untested-model', 'supports_vision' => true, 'active' => true]);
		AiModel::query()->create(['ai_provider_id' => $provider->id, 'model_id' => 'text-only-model', 'supports_vision' => false, 'active' => true])
			->forceFill(['last_test_ok' => true])->save();
		
		$settingId = DB::table('settings')->where('name', 'ai')->value('id');
		$response = $this->actingAs($this->admin)->get('/admin/settings/' . $settingId . '/edit');
		
		$response->assertOk();
		$response->assertSee('ready-vision-model');
		$response->assertDontSee('untested-model');
		// Text-only models suit writing help only, not the features that read photos
		$this->assertSame(1, substr_count((string)$response->getContent(), 'text-only-model'));
		$response->assertSee('AI writing help');
		$response->assertSee('AI listing assistant');
		$response->assertDontSee('Quick post');
		$response->assertDontSee('quick post');
	}
	
	public function test_load_models_lists_the_provider_models_as_json(): void
	{
		// Arrange
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'api_key' => 'k-load-123456', 'active' => true]);
		\Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::response([
			'data'     => [['id' => 'claude-sonnet-5-5', 'display_name' => 'Claude Sonnet 5.5', 'capabilities' => ['image_input' => ['supported' => true]]]],
			'has_more' => false,
		])]);
		
		// Act
		$response = $this->actingAs($this->admin)->getJson('/admin/ai_models/available?provider=' . $provider->id);
		
		// Assert
		$response->assertOk()
			->assertJsonPath('success', true)
			->assertJsonPath('data.0.id', 'claude-sonnet-5-5')
			->assertJsonPath('data.0.vision', true)
			->assertJsonPath('data.0.input_price', 2);
		$this->assertStringNotContainsString('k-load-123456', $response->getContent());
	}
	
	public function test_load_models_reports_a_provider_error(): void
	{
		// Arrange
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'openai', 'api_key' => 'sk-bad-123456789', 'active' => true]);
		\Illuminate\Support\Facades\Http::fake(['api.openai.com/*' => \Illuminate\Support\Facades\Http::response(['error' => ['message' => 'bad key sk-bad-123456789']], 401)]);
		
		// Act
		$response = $this->actingAs($this->admin)->getJson('/admin/ai_models/available?provider=' . $provider->id);
		
		// Assert
		$response->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('data', null);
		$this->assertStringContainsString('refused the API key', (string)$response->json('error'));
		$this->assertStringNotContainsString('sk-bad-123456789', $response->getContent());
	}
	
	public function test_load_models_needs_a_known_provider_and_a_super_admin(): void
	{
		// Arrange
		$staff = User::query()->forceCreate([
			'name' => 'Staff', 'email' => 'staff@example.test', 'auth_field' => 'email', 'password' => bcrypt(str()->random(32)),
			'email_verified_at' => now(), 'phone_verified_at' => now(),
		]);
		$staff->givePermissionTo(Permission::getStaffPermissions());
		
		// Act & Assert
		$this->actingAs($this->admin)->getJson('/admin/ai_models/available?provider=999')->assertStatus(404);
		$this->assertNotSame(200, $this->actingAs($staff)->getJson('/admin/ai_models/available?provider=1')->status());
	}
	
	public function test_the_model_form_has_the_load_models_button(): void
	{
		// Act & Assert
		$this->actingAs($this->admin)->get('/admin/ai_models/create')
			->assertOk()
			->assertSee('Load models')
			->assertSee('data-catalog-url', false);
	}
	
	private function rowsHtml($response): string
	{
		return collect($response->json('data'))->flatten()->implode(' ');
	}
}
