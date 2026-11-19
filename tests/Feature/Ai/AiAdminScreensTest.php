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
		$this->assertStringContainsString('•••• 9876', $html);
		$this->assertStringContainsString('Test connection', $html);
		$this->assertStringNotContainsString('secret-key-9876', $rows->getContent());
		$this->actingAs($this->admin)->get('/admin/ai_providers/create')->assertOk()->assertSee('Anthropic (Claude)');
		
		$edit = $this->actingAs($this->admin)->get('/admin/ai_providers/' . AiProvider::query()->value('id') . '/edit');
		$edit->assertOk();
		$edit->assertSee('•••• 9876', false);
		$edit->assertDontSee('secret-key-9876');
	}
	
	public function test_a_provider_is_created_with_an_encrypted_key(): void
	{
		$this->actingAs($this->admin)->post('/admin/ai_providers', [
			'name'    => 'OpenAI account',
			'driver'  => 'openai',
			'api_key' => 'sk-test-abcdef123456',
			'active'  => 1,
		])->assertRedirect();
		
		$provider = AiProvider::query()->sole();
		$this->assertSame('sk-test-abcdef123456', $provider->api_key);
		$this->assertStringNotContainsString('abcdef123456', DB::table('ai_providers')->value('api_key'));
	}
	
	public function test_saving_a_provider_with_an_empty_key_keeps_the_stored_key(): void
	{
		$provider = AiProvider::query()->create(['name' => 'Main', 'driver' => 'anthropic', 'api_key' => 'keep-me-1111', 'active' => true]);
		
		$this->actingAs($this->admin)->put('/admin/ai_providers/' . $provider->id, [
			'id'      => $provider->id,
			'name'    => 'Main renamed',
			'driver'  => 'anthropic',
			'api_key' => '',
			'active'  => 1,
		])->assertRedirect();
		
		$provider->refresh();
		$this->assertSame('Main renamed', $provider->name);
		$this->assertSame('keep-me-1111', $provider->api_key);
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
	
	public function test_the_ai_settings_screen_lists_only_tested_vision_models(): void
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
		$response->assertDontSee('text-only-model');
	}
	
	private function rowsHtml($response): string
	{
		return collect($response->json('data'))->flatten()->implode(' ');
	}
}
