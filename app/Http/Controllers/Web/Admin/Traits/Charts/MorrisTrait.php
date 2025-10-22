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

namespace App\Http\Controllers\Web\Admin\Traits\Charts;

use App\Helpers\Common\Date;
use App\Helpers\Common\Date\TimeZoneManager;
use App\Models\Post;
use App\Models\User;

trait MorrisTrait
{
	private string $dateTimeFormat = 'Y-m-d H:i:s';
	
	/**
	 * Graphic chart: Get listings number per day (for X days)
	 *
	 * @param int $daysNumber
	 * @return array
	 */
	private function getLatestListingsForMorris(int $daysNumber = 7): array
	{
		$tz = TimeZoneManager::getContextualTimeZone();
		
		// Init.
		$daysNumber = (is_numeric($daysNumber)) ? $daysNumber : 7;
		
		// Get start date
		$startDate = now($tz)->subDays($daysNumber);
		$startDate = $startDate->startOfDay()->format($this->dateTimeFormat);
		
		// Get end date
		$endDate = now($tz);
		$endDate = $endDate->endOfDay()->format($this->dateTimeFormat);
		
		// Select only required columns
		$select = ['id', 'created_at'];
		
		// Get listings from latest $daysNumber days
		$activatedPosts = Post::query()
			->withoutAppends()
			->verified()
			->where('created_at', '>=', $startDate)
			->where('created_at', '<=', $endDate)
			->orderByDesc('created_at')
			->get($select);
		
		$unactivatedPosts = Post::query()
			->withoutAppends()
			->unverified()
			->where('created_at', '>=', $startDate)
			->where('created_at', '<=', $endDate)
			->orderByDesc('created_at')
			->get($select);
		
		// Get listings number per day
		$currentDate = now($tz);
		$stats = [];
		for ($i = 1; $i <= $daysNumber; $i++) {
			$dateObj = ($i == 1) ? $currentDate : $currentDate->subDay();
			
			// Get start & end date|time
			$startDate = $dateObj->copy()->startOfDay()->format($this->dateTimeFormat);
			$endDate = $dateObj->copy()->endOfDay()->format($this->dateTimeFormat);
			
			// Count the listings of this day
			$countActivatedPosts = collect($activatedPosts)
				->filter(function ($item) use ($startDate, $endDate) {
					return (
						strtotime($item->created_at) >= strtotime($startDate)
						&& strtotime($item->created_at) <= strtotime($endDate)
					);
				})->count();
			
			$countUnactivatedPosts = collect($unactivatedPosts)
				->filter(function ($item) use ($startDate, $endDate) {
					return (
						strtotime($item->created_at) >= strtotime($startDate)
						&& strtotime($item->created_at) <= strtotime($endDate)
					);
				})->count();
			
			$stats['posts'][$i]['y'] = mb_ucfirst(Date::format($dateObj, 'stats'));
			$stats['posts'][$i]['activated'] = $countActivatedPosts;
			$stats['posts'][$i]['unactivated'] = $countUnactivatedPosts;
		}
		
		$stats['posts'] = array_reverse($stats['posts'], true);
		
		$data = json_encode(array_values($stats['posts']), JSON_NUMERIC_CHECK);
		
		return [
			'title' => trans('admin.Listings Stats'),
			'data'  => $data,
		];
	}
	
	/**
	 * Graphic chart: Get users number per day (for X days)
	 *
	 * @param int $daysNumber
	 * @return array
	 */
	private function getLatestUsersForMorris(int $daysNumber = 7): array
	{
		$tz = TimeZoneManager::getContextualTimeZone();
		
		// Init.
		$daysNumber = (is_numeric($daysNumber)) ? $daysNumber : 7;
		
		// Get start date
		$startDate = now($tz)->subDays($daysNumber);
		$startDate = $startDate->startOfDay()->format($this->dateTimeFormat);
		
		// Get end date
		$endDate = now($tz);
		$endDate = $endDate->endOfDay()->format($this->dateTimeFormat);
		
		// Select only required columns
		$select = ['id', 'created_at'];
		
		// Get listings from latest $daysNumber days
		$activatedUsers = User::query()
			->withoutAppends()
			->doesntHave('permissions')
			->verified()
			->where('created_at', '>=', $startDate)
			->where('created_at', '<=', $endDate)
			->get($select);
		
		$unactivatedUsers = User::query()
			->withoutAppends()
			->doesntHave('permissions')
			->unverified()
			->where('created_at', '>=', $startDate)
			->where('created_at', '<=', $endDate)
			->get($select);
		
		// Get listings number per day
		$currentDate = now($tz);
		$stats = [];
		for ($i = 1; $i <= $daysNumber; $i++) {
			$dateObj = ($i == 1) ? $currentDate : $currentDate->subDay();
			
			// Get start & end date|time
			$startDate = $dateObj->copy()->startOfDay()->format($this->dateTimeFormat);
			$endDate = $dateObj->copy()->endOfDay()->format($this->dateTimeFormat);
			
			// Count the listings of this day
			$countActivatedUsers = collect($activatedUsers)
				->filter(function ($item) use ($startDate, $endDate) {
					return (
						strtotime($item->created_at) >= strtotime($startDate)
						&& strtotime($item->created_at) <= strtotime($endDate)
					);
				})->count();
			
			$countUnactivatedUsers = collect($unactivatedUsers)
				->filter(function ($item) use ($startDate, $endDate) {
					return (
						strtotime($item->created_at) >= strtotime($startDate)
						&& strtotime($item->created_at) <= strtotime($endDate)
					);
				})->count();
			
			$stats['users'][$i]['y'] = mb_ucfirst(Date::format($dateObj, 'stats'));
			$stats['users'][$i]['activated'] = $countActivatedUsers;
			$stats['users'][$i]['unactivated'] = $countUnactivatedUsers;
		}
		
		$stats['users'] = array_reverse($stats['users'], true);
		
		$data = json_encode(array_values($stats['users']), JSON_NUMERIC_CHECK);
		
		return [
			'title' => trans('admin.Users Stats'),
			'data'  => $data,
		];
	}
}
