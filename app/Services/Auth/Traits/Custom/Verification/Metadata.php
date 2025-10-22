<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Services\Auth\Traits\Custom\Verification;

use App\Http\Resources\PasswordResetResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\PasswordReset;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;

trait Metadata
{
	protected array $entitiesMetadata = [
		'users'    => [
			'key'        => 'users',
			'model'      => User::class,
			'resource'   => UserResource::class,
			'scopes'     => [VerifiedScope::class],
			'nameColumn' => 'name',
		],
		'posts'    => [
			'key'        => 'posts',
			'model'      => Post::class,
			'resource'   => PostResource::class,
			'scopes'     => [VerifiedScope::class, ReviewedScope::class],
			'nameColumn' => 'contact_name',
		],
		'password' => [
			'key'        => 'password',
			'model'      => PasswordReset::class,
			'resource'   => PasswordResetResource::class,
			'scopes'     => [],
			'nameColumn' => null,
		],
	];
	
	protected string $metadataNotFoundMessage = "The metadata for the entity '%s' cannot be found.";
	
	/**
	 * Get the entity metadata
	 *
	 * @param string|null $metadataKey
	 * @return array|null
	 */
	protected function getEntityMetadata(?string $metadataKey = null): ?array
	{
		if (empty($metadataKey)) {
			$metadataKey = 'undefined';
		}
		
		return $this->entitiesMetadata[$metadataKey] ?? null;
	}
}
