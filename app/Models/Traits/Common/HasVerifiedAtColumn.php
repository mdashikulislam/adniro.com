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

namespace App\Models\Traits\Common;

use App\Helpers\Common\Arr;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait HasVerifiedAtColumn
{
	public function crudVerifiedEmailColumn(?Panel $xPanel = null, array $column = []): ?string
	{
		if (!Arr::keyExists('email_verified_at', $this)) return null;
		
		// Get checkbox
		$out = ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'email_verified_at', $this->email_verified_at);
		
		// Get all entity's data
		$entity = self::find($this->{$this->primaryKey});
		
		if (empty($entity->email)) {
			return checkboxDisplay($this->email_verified_at);
		}
		
		if (empty($entity->email_verified_at)) {
			// ToolTip
			$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.To') . ': ' . $entity->email . '"';
			
			// Get entity's language (If exists)
			$params = ['goTo' => request()->path()];
			if (isset($entity->language_code)) {
				$locale = array_key_exists($entity->language_code, getSupportedLanguages())
					? $entity->language_code
					: config('app.locale');
				$params['locale'] = $locale;
			}
			
			// Show re-send verification message link
			$entityMetadataKey = ($this->getTable() == 'users') ? 'users' : 'posts';
			$actionUrl = urlGen()->resendEmailVerification($entityMetadataKey, $this->{$this->primaryKey});
			$actionUrl = urlBuilder($actionUrl)->setParameters($params)->toString();
			
			// HTML Link
			$out .= ' &nbsp;';
			$out .= '<a class="btn btn-light btn-xs" href="' . $actionUrl . '" ' . $toolTip . '>';
			$out .= '<i class="fa-regular fa-paper-plane"></i> ';
			$out .= trans('admin.Re-send link');
			$out .= '</a>';
			
			return $out;
		}
		
		// Get social icon (if exists) - Only for User model
		if ($this->getTable() == 'users') {
			if (!empty($entity)) {
				// Load the user's socialLogins
				$entity->loadMissing(['socialLogins']);
				
				if ($entity->socialLogins->count() > 0) {
					foreach ($entity->socialLogins as $socialLogin) {
						if ($socialLogin->provider == 'facebook') {
							$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.registered_from', ['provider' => 'Facebook']) . '"';
							$out .= ' &nbsp;<i class="admin-single-icon fa-brands fa-square-facebook" style="color: #3b5998;" ' . $toolTip . '></i>';
						}
						if ($socialLogin->provider == 'linkedin') {
							$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.registered_from', ['provider' => 'LinkedIn']) . '"';
							$out .= ' &nbsp;<i class="admin-single-icon fa-brands fa-linkedin" style="color: #4682b4;" ' . $toolTip . '></i>';
						}
						if ($socialLogin->provider == 'twitter-oauth-2') {
							$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.registered_from', ['provider' => 'Twitter (OAuth 2.0)']) . '"';
							$out .= ' &nbsp;<i class="admin-single-icon fa-brands fa-square-x-twitter" style="color: #0099d4;" ' . $toolTip . '></i>';
						}
						if ($socialLogin->provider == 'twitter') {
							$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.registered_from', ['provider' => 'Twitter (OAuth 1.0)']) . '"';
							$out .= ' &nbsp;<i class="admin-single-icon fa-brands fa-square-x-twitter" style="color: #0099d4;" ' . $toolTip . '></i>';
						}
						if ($socialLogin->provider == 'google') {
							$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.registered_from', ['provider' => 'Google']) . '"';
							$out .= ' &nbsp;<i class="admin-single-icon fa-brands fa-square-google-plus" style="color: #d34836;" ' . $toolTip . '></i>';
						}
					}
				}
			}
		}
		
		return $out;
	}
	
	public function crudVerifiedPhoneColumn(?Panel $xPanel = null, array $column = []): ?string
	{
		if (!Arr::keyExists('phone_verified_at', $this)) return null;
		
		// Get checkbox
		$out = ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'phone_verified_at', $this->phone_verified_at);
		
		// Get all entity's data
		$entity = self::find($this->{$this->primaryKey});
		
		if (empty($entity->phone)) {
			return checkboxDisplay($this->phone_verified_at);
		}
		
		if (empty($entity->phone_verified_at)) {
			// ToolTip
			$toolTip = 'data-bs-toggle="tooltip" title="' . trans('admin.To') . ': ' . $entity->phone . '"';
			
			// Get entity's language (If exists)
			$params = ['goTo' => request()->path()];
			if (isset($entity->language_code)) {
				$locale = (array_key_exists($entity->language_code, getSupportedLanguages()))
					? $entity->language_code
					: config('app.locale');
				$params['locale'] = $locale;
			}
			
			// Show re-send verification message code
			$entityMetadataKey = ($this->getTable() == 'users') ? 'users' : 'posts';
			$actionUrl = urlGen()->resendSmsVerification($entityMetadataKey, $this->{$this->primaryKey});
			$actionUrl = urlBuilder($actionUrl)->setParameters($params)->toString();
			
			// HTML Link
			$out .= ' &nbsp;';
			$out .= '<a class="btn btn-light btn-xs" href="' . $actionUrl . '" ' . $toolTip . '>';
			$out .= '<i class="fa-solid fa-mobile-screen-button"></i> ';
			$out .= trans('admin.Re-send code');
			$out .= '</a>';
		}
		
		return $out;
	}
}
