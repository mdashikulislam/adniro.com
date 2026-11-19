<?php

namespace App\Services\Ai\Moderation;

/*
 * Values of posts.moderation_status (adniro:ai).
 * Only "approved" listings are public. The other three are hidden from everyone
 * except the owner and staff.
 */

final class ModerationStatus
{
	public const APPROVED = 'approved';
	public const PENDING = 'pending';
	public const NEEDS_REVIEW = 'needs_review';
	public const REJECTED = 'rejected';
	
	public const ALL = [self::APPROVED, self::PENDING, self::NEEDS_REVIEW, self::REJECTED];
	
	/**
	 * A missing or empty value counts as approved, like the column default,
	 * so arrays built before the column existed stay visible.
	 */
	public static function isPublic(?string $status): bool
	{
		return $status === null || $status === '' || $status === self::APPROVED;
	}
	
	public static function isValid(?string $status): bool
	{
		return in_array($status, self::ALL, true);
	}
}
