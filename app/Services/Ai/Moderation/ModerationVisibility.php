<?php

namespace App\Services\Ai\Moderation;

use App\Models\Permission;

/*
 * Who may see a listing that moderation has hidden (adniro:ai).
 * Used where the code loads a listing without the visibility scopes for any visitor:
 * the "?preview=1" show page and the API show endpoint.
 */

final class ModerationVisibility
{
	/**
	 * @param array|object|null $post
	 * @return bool
	 */
	public static function canSee(array|object|null $post): bool
	{
		if (empty($post)) {
			return false;
		}
		
		if (ModerationStatus::isPublic(data_get($post, 'moderation_status'))) {
			return true;
		}
		
		if (isFromAdminPanel()) {
			return true;
		}
		
		$authUser = auth(getAuthGuard())->user();
		if (empty($authUser)) {
			return false;
		}
		
		$ownerId = data_get($post, 'user_id');
		if (!empty($ownerId) && (string)$ownerId === (string)$authUser->getAuthIdentifier()) {
			return true;
		}
		
		return $authUser->can(Permission::getStaffPermissions());
	}
}
