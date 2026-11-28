<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Admin > Packages: saving a package whose form has a "belongs to" field (its currency) must not
 * write the panel's in-memory was_manually_changed flag to the database (packages has no such column).
 */
class AdminPackageEditTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private User $admin;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		$this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

		$permissions = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
		foreach ($permissions as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
		$this->admin = $this->stockMember(90);
		$this->admin->givePermissionTo($permissions);
	}

	public function test_an_admin_can_change_a_promotion_package_price(): void
	{
		// Arrange
		$package = Package::query()->withoutGlobalScopes()->where('type', 'promotion')->firstOrFail();
		$newPrice = (float)$package->price + 1.5;

		// Act
		$response = $this->actingAs($this->admin)->put('/admin/packages/promotion/' . $package->id, [
			'id'            => $package->id,
			'type'          => 'promotion',
			'name'          => $package->getTranslation('name', 'en', false) ?: 'Top listing',
			'short_name'    => $package->getTranslation('short_name', 'en', false) ?: 'Top',
			'price'         => $newPrice,
			'currency_code' => $package->currency_code,
			'description'   => $package->getTranslation('description', 'en', false) ?: 'Shown at the top',
			'active'        => 1,
		]);

		// Assert
		$flash = collect(session('flash_messages', []))->where('level', 'error')->pluck('message')->all();
		$this->assertSame([], $flash);
		$response->assertSessionHasNoErrors();
		$saved = (float)DB::table('packages')->where('id', $package->id)->value('price');
		$this->assertSame($newPrice, $saved);
	}
}
