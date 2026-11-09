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

namespace App\Helpers\Services\Search\Traits\Filters;

trait AuthorFilter
{
	/**
	 * Is the search limited to a given seller (by ID or username)?
	 *
	 * @return bool
	 */
	protected function isAuthorSearch(): bool
	{
		$userId = data_get($this->input, 'userId');
		$username = data_get($this->input, 'username');
		
		return ((is_numeric($userId) && !empty($userId)) || (is_string($username) && trim($username) !== ''));
	}
	
	protected function applyAuthorFilter(): void
	{
		if (!isset($this->posts)) {
			return;
		}
		
		$userId = data_get($this->input, 'userId');
		$username = data_get($this->input, 'username');
		
		$userId = is_numeric($userId) ? $userId : null;
		$username = is_string($username) ? $username : null;
		
		if (empty($userId) && empty($username)) {
			return;
		}
		
		if (!empty($userId)) {
			$this->posts->where('user_id', $userId);
		}
		
		if (!empty($username)) {
			// Listings of the user having this username,
			// or listings published without account by the poster having this username
			$postsTable = $this->postsTable;
			$this->posts->where(function ($query) use ($username, $postsTable) {
				$query->whereHas('user', fn ($query) => $query->where('username', $username))
					->orWhere(function ($query) use ($username, $postsTable) {
						$query->whereNull($postsTable . '.user_id')->where($postsTable . '.guest_username', $username);
					});
			});
		}
	}
}
