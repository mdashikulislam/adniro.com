<?php

namespace Tests\Feature\Ai;

use App\Http\Controllers\Web\Admin\AiPostLimitController;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Security fixes from the review of the AI branch (docs/professional-website-plan.md, phase 2).
 */

class AiSecurityFixesTest extends TestCase
{
	use RefreshDatabase, ListingAssistantFixtures;
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpListingAssistant();
		$this->ai->answer($this->draftAnswer());
	}
	
	public function test_staff_cannot_change_a_super_admins_ai_limit(): void
	{
		// Arrange
		$superAdmin = $this->admin();
		$member = $this->user(7);
		$staff = $this->user(9);
		Permission::query()->firstOrCreate(['name' => AiPostLimitController::PERMISSION, 'guard_name' => 'web']);
		$staff->givePermissionTo(array_merge(Permission::getStaffPermissions(), [AiPostLimitController::PERMISSION]));
		
		// Act
		$this->actingAs($staff)->post('/admin/users/' . $superAdmin->id . '/ai-post-limit', ['action' => 'save', 'ai_post_limit' => 0]);
		$this->actingAs($staff)->post('/admin/users/' . $member->id . '/ai-post-limit', ['action' => 'save', 'ai_post_limit' => 3]);
		
		// Assert
		$this->assertNull($superAdmin->fresh()->ai_post_limit);
		$this->assertSame(3, (int)$member->fresh()->ai_post_limit, 'ordinary members can still be changed');
	}
	
	public function test_only_super_admins_open_backups(): void
	{
		// Arrange
		$superAdmin = $this->admin();
		$staff = $this->user(9);
		$staff->givePermissionTo(Permission::getStaffPermissions());
		
		// Act
		$denied = $this->actingAs($staff)->get('/admin/backups');
		
		// Assert
		$this->assertTrue($denied->isForbidden() || $denied->isRedirect(), 'staff are turned away');
		$this->assertFalse($this->actingAs($superAdmin)->get('/admin/backups')->isForbidden());
	}
	
	public function test_all_guests_together_stop_at_the_daily_cap(): void
	{
		// Arrange
		$this->setAi(['listing_assistant_guests' => '1', 'listing_assistant_guest_limit' => '10']);
		config(['ai_features.listing_assistant.guest_daily_cap' => 1]);
		
		// Act
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->postJson('/posts/create/assistant/fill', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);
		$second = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.6'])->postJson('/posts/create/assistant/fill', ['text' => 'iPhone 12 red with box']);
		
		// Assert
		$second->assertStatus(429);
	}
	
	public function test_a_forged_forwarded_for_header_is_ignored_from_an_untrusted_proxy(): void
	{
		// Arrange: only 10.9.9.9 may set X-Forwarded-For
		config(['app.trusted_proxies' => '10.9.9.9']);
		$this->setAi(['listing_assistant_guests' => '1', 'listing_assistant_guest_limit' => '1']);
		$from = fn (string $forged) => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => $forged]);
		
		// Act
		$from('1.1.1.1')->postJson('/posts/create/assistant/fill', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);
		$second = $from('2.2.2.2')->postJson('/posts/create/assistant/fill', ['text' => 'iPhone 12 red with box']);
		
		// Assert: both count against 10.0.0.5
		$second->assertStatus(429);
	}
}
