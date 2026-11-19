<?php

namespace Tests\Feature\Ai;

use App\Http\Middleware\SuperAdminOnly;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/*
 * The AI provider and model screens hold API keys: only super admins may open them.
 */

class SuperAdminOnlyTest extends TestCase
{
	use RefreshDatabase;
	
	public function test_guests_are_refused(): void
	{
		$this->assertForbidden();
	}
	
	public function test_a_user_without_the_super_admin_permissions_is_refused(): void
	{
		$user = $this->makeUser('staff@example.test');
		$this->createPermissions();
		$user->givePermissionTo(Permission::getStaffPermissions());
		$this->actingAs($user);
		
		$this->assertForbidden();
	}
	
	public function test_a_super_admin_is_let_through(): void
	{
		$user = $this->makeUser('owner@example.test');
		$this->createPermissions();
		$user->givePermissionTo(array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()));
		$this->actingAs($user);
		
		$response = (new SuperAdminOnly())->handle(Request::create('/admin/ai_providers'), fn () => new Response('ok'));
		
		$this->assertSame('ok', $response->getContent());
	}
	
	private function assertForbidden(): void
	{
		try {
			(new SuperAdminOnly())->handle(Request::create('/admin/ai_providers'), fn () => new Response('ok'));
			$this->fail('The request should have been refused.');
		} catch (HttpException $e) {
			$this->assertSame(403, $e->getStatusCode());
		}
	}
	
	private function createPermissions(): void
	{
		foreach (array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()) as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
	}
	
	private function makeUser(string $email): User
	{
		return User::query()->forceCreate([
			'name'              => 'Test user',
			'email'             => $email,
			'auth_field'        => 'email',
			'password'          => bcrypt(str()->random(32)),
			'email_verified_at' => now(),
			'phone_verified_at' => now(),
		]);
	}
}
