<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
 * Seller's public page (/user/{username}): profile, statistics & followers.
 *
 * A seller is:
 * - a user (type "user"), found by its username;
 * - or the poster of listings published without account (type "guest"),
 *   found by the username generated for those listings (posts.guest_username).
 */
class SellerService
{
	/**
	 * @param string|null $username
	 * @return array|null
	 */
	public static function find(?string $username): ?array
	{
		$username = trim((string)$username);
		if ($username === '') {
			return null;
		}
		
		$user = User::query()->withoutGlobalScopes()->where('username', $username)->first();
		if (!empty($user)) {
			return self::fromUser($user);
		}
		
		$postsTable = (new Post())->getTable();
		$post = DB::table($postsTable)
			->whereNull('user_id')
			->where('guest_username', $username)
			->orderByDesc('id')
			->first(['guest_username', 'contact_name', 'country_code']);
		if (empty($post)) {
			return null;
		}
		
		$since = DB::table($postsTable)->whereNull('user_id')->where('guest_username', $username)->min('created_at');
		
		return [
			'type'         => 'guest',
			'user_id'      => null,
			'username'     => $post->guest_username,
			'name'         => mb_ucwords((string)$post->contact_name),
			'photo_url'    => null,
			'cover_url'    => null,
			'about'        => null,
			'since'        => !empty($since) ? Carbon::parse($since) : null,
			'is_verified'  => false,
			'country_code' => $post->country_code,
		];
	}
	
	/**
	 * @param int|null $userId
	 * @return array|null
	 */
	public static function findByUserId(?int $userId): ?array
	{
		if (empty($userId)) {
			return null;
		}
		
		$user = User::query()->withoutGlobalScopes()->where('id', $userId)->first();
		
		return !empty($user) ? self::fromUser($user) : null;
	}
	
	/**
	 * @param \App\Models\User $user
	 * @return array
	 */
	private static function fromUser(User $user): array
	{
		// The default avatar is replaced by the seller's initials on the page
		$photoUrl = null;
		if (!empty($user->getRawOriginal('photo_path'))) {
			try {
				$photoUrl = $user->photo_url;
			} catch (Throwable $e) {
			}
		}
		
		$since = $user->getRawOriginal('created_at');
		
		return [
			'type'         => 'user',
			'user_id'      => (int)$user->getKey(),
			'username'     => $user->username,
			'name'         => (string)$user->name,
			'photo_url'    => $photoUrl,
			'cover_url'    => !empty($user->cover_path) ? fileUrl($user->cover_path) : null,
			'about'        => trim(strip_tags((string)$user->about)),
			'since'        => !empty($since) ? Carbon::parse($since) : null,
			'is_verified'  => (!empty($user->email_verified_at) && !empty($user->phone_verified_at)),
			'country_code' => $user->country_code,
		];
	}
	
	/**
	 * Statistics shown on the seller's page
	 *
	 * @param array $seller
	 * @param int $listings
	 * @return array
	 */
	public static function stats(array $seller, int $listings = 0): array
	{
		$views = (int)self::liveListings($seller)->sum('visits');
		$countries = (int)self::liveListings($seller)->distinct()->count('country_code');
		
		return [
			'listings'  => $listings,
			'followers' => self::followersCount($seller),
			'following' => self::followingCount($seller),
			'views'     => $views,
			'countries' => $countries,
		];
	}
	
	/**
	 * @param array $seller
	 * @return int
	 */
	public static function followersCount(array $seller): int
	{
		return (int)self::followsOf($seller)->count();
	}
	
	/**
	 * @param array $seller
	 * @return int
	 */
	public static function followingCount(array $seller): int
	{
		if (empty($seller['user_id'])) {
			return 0;
		}
		
		return (int)DB::table('follows')->where('follower_id', $seller['user_id'])->count();
	}
	
	/**
	 * @param array $seller
	 * @param int|null $followerId
	 * @return bool
	 */
	public static function isFollowedBy(array $seller, ?int $followerId): bool
	{
		if (empty($followerId)) {
			return false;
		}
		
		return self::followsOf($seller)->where('follower_id', $followerId)->exists();
	}
	
	/**
	 * Follow the seller, or stop following it
	 *
	 * @param array $seller
	 * @param int $followerId
	 * @return bool Is the seller followed now?
	 */
	public static function toggleFollow(array $seller, int $followerId): bool
	{
		if (self::isFollowedBy($seller, $followerId)) {
			self::followsOf($seller)->where('follower_id', $followerId)->delete();
			
			return false;
		}
		
		// insertOrIgnore(): two simultaneous clicks hit the unique key instead of creating a duplicate
		DB::table('follows')->insertOrIgnore([
			'follower_id'             => $followerId,
			'followed_user_id'        => $seller['user_id'] ?? null,
			'followed_guest_username' => empty($seller['user_id']) ? $seller['username'] : null,
			'created_at'              => now(),
		]);
		
		return true;
	}
	
	/**
	 * @param array $seller
	 * @return string|null
	 */
	public static function countryName(array $seller): ?string
	{
		if (empty($seller['country_code'])) {
			return null;
		}
		
		try {
			$country = Country::query()->withoutGlobalScopes()->where('code', $seller['country_code'])->first(['code', 'name']);
			
			return !empty($country) ? (string)$country->name : null;
		} catch (Throwable $e) {
			return null;
		}
	}
	
	/**
	 * @param array $seller
	 * @return \Illuminate\Database\Query\Builder
	 */
	private static function followsOf(array $seller)
	{
		$query = DB::table('follows');
		
		if (!empty($seller['user_id'])) {
			return $query->where('followed_user_id', $seller['user_id']);
		}
		
		return $query->where('followed_guest_username', $seller['username'] ?? '');
	}
	
	/**
	 * The seller's listings that are online (not archived, not deleted)
	 *
	 * @param array $seller
	 * @return \Illuminate\Database\Query\Builder
	 */
	private static function liveListings(array $seller)
	{
		$query = DB::table((new Post())->getTable())
			->whereNull('archived_at')
			->whereNull('deleted_at')
			->whereNotNull('email_verified_at')
			->whereNotNull('phone_verified_at');
		
		if (!empty($seller['user_id'])) {
			return $query->where('user_id', $seller['user_id']);
		}
		
		return $query->whereNull('user_id')->where('guest_username', $seller['username'] ?? '');
	}
}
