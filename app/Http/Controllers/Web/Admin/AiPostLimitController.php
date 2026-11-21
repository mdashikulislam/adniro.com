<?php

namespace App\Http\Controllers\Web\Admin;

use App\Models\Permission;
use App\Models\User;
use App\Services\ListingAssistant\AiPostLimit;
use Illuminate\Http\Request;

/*
 * Admin > Users > AI limit: a different AI post limit for one user, or a reset of their count
 * (adniro:ai). For super admins and staff allowed to edit users. The action names are not CRUD
 * names, so the panel's route-permission parser adds nothing and allows() decides.
 */

class AiPostLimitController extends Controller
{
	public const PERMISSION = 'admin.user.edit';
	
	public static function allows($user): bool
	{
		return !empty($user) && (userHasSuperAdminPermissions() || doesUserHavePermission($user, self::PERMISSION));
	}
	
	public function limitForm($id, AiPostLimit $limit)
	{
		abort_unless(self::allows(auth()->user()), 403);
		$user = $this->findUser($id);
		
		return view('admin.ai.user-limit', ['user' => $user, 'status' => $limit->status($user, null)]);
	}
	
	public function saveLimit($id, Request $request)
	{
		abort_unless(self::allows(auth()->user()), 403);
		$user = $this->findUser($id);
		
		if ($request->input('action') === 'reset') {
			$user->forceFill(['ai_post_reset_at' => now()])->saveQuietly();
			notification(trans('ai.user_limit_reset_done'), 'success');
			
			return redirect()->back();
		}
		
		$data = $request->validate(['ai_post_limit' => ['nullable', 'integer', 'min:0', 'max:100000']]);
		$value = ($data['ai_post_limit'] ?? null);
		$user->forceFill(['ai_post_limit' => ($value === null || $value === '') ? null : (int)$value])->saveQuietly();
		
		notification(trans('ai.user_limit_saved'), 'success');
		
		return redirect()->back();
	}
	
	private function findUser($id): User
	{
		$user = User::query()->withoutGlobalScopes()->find((int)$id);
		abort_if(empty($user), 404);
		
		// Only a super admin may change a super admin's AI limit
		abort_if(self::isSuperAdmin($user) && !userHasSuperAdminPermissions(), 403, trans('admin.forbidden'));
		
		return $user;
	}
	
	/** Same test as userHasSuperAdminPermissions(), for any user. */
	private static function isSuperAdmin(User $user): bool
	{
		$permissions = collect(Permission::getSuperAdminPermissions())
			->reject(fn ($item) => in_array($item, Permission::getStaffPermissions()))
			->all();
		
		return !empty($permissions) && doesUserHavePermission($user, $permissions);
	}
}
