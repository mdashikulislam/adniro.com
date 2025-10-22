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

use App\Helpers\Common\Arr;
use App\Models\Permission;

/**
 * Checkbox Display
 *
 * @param $fieldValue
 * @param $id
 * @return string
 */
function checkboxDisplay($fieldValue, $id = null): string
{
	$attrId = !empty($id) ? 'id="' . $id . '"' : '';
	
	$isChecked = is_bool($fieldValue) ? $fieldValue : !empty($fieldValue);
	
	// fa-square-o | fa-check-square-o
	// fa-toggle-off | fa-toggle-on
	if ($isChecked) {
		return '<i ' . $attrId . ' class="admin-single-icon fa-solid fa-toggle-on" aria-hidden="true"></i>';
	} else {
		return '<i ' . $attrId . ' class="admin-single-icon fa-solid fa-toggle-off" aria-hidden="true"></i>';
	}
}

/**
 * Ajax Checkbox Display
 *
 * @param $id
 * @param $table
 * @param $field
 * @param null $fieldValue
 * @return string
 */
function ajaxCheckboxDisplay($id, $table, $field, $fieldValue = null): string
{
	$lineId = $field . $id;
	$lineId = str_replace('.', '', $lineId); // fix JS bug (in admin layout)
	$data = 'data-table="' . $table . '"
			data-field="' . $field . '"
			data-line-id="' . $lineId . '"
			data-id="' . $id . '"
			data-value="' . $fieldValue . '"';
	
	// Decoration
	$html = checkboxDisplay($fieldValue, $lineId);
	
	return '<a href="" class="ajax-request" ' . $data . '>' . $html . '</a>';
}

/**
 * Advanced Ajax Checkbox Display
 *
 * @param $id
 * @param $table
 * @param $field
 * @param null $fieldValue
 * @return string
 */
function installAjaxCheckboxDisplay($id, $table, $field, $fieldValue = null): string
{
	$lineId = $field . $id;
	$lineId = str_replace('.', '', $lineId); // fix JS bug (in admin layout)
	$data = 'data-table="' . $table . '"
			data-field="' . $field . '"
			data-line-id="' . $lineId . '"
			data-id="' . $id . '"
			data-value="' . $fieldValue . '"';
	
	// Decoration
	$html = checkboxDisplay($fieldValue, $lineId);
	$html = '<a href="" class="ajax-request" ' . $data . '>' . $html . '</a>';
	
	// Install country's decoration
	$html .= ' &nbsp;';
	if ($fieldValue == 1) {
		$html .= '<a href="" id="install' . $id . '" class="ajax-request btn btn-xs btn-success text-white" ' . $data . '>';
		$html .= '<i class="fa-solid fa-download"></i> ' . trans('admin.Installed');
		$html .= '</a>';
	} else {
		$html .= '<a href="" id="install' . $id . '" class="ajax-request btn btn-xs btn-light" ' . $data . '>';
		$html .= '<i class="fa-solid fa-download"></i> ' . trans('admin.Install');
		$html .= '</a>';
	}
	
	return $html;
}

/**
 * Generate the Post's link from the Admin panel
 *
 * @param $post
 * @return string
 */
function getPostUrl($post): string
{
	// Get the listing's possible payment info
	$paymentInfo = '';
	if (!empty($post->payment)) {
		$info = ' (' . $post->payment->expiry_info . ')';
		$class = 'text-' . $post->payment->css_class_variant;
		$packageName = $post->payment->package?->short_name ?? t('unknown_package');
		
		$paymentInfo = ' <i class="fa-solid fa-circle-check ' . $class . '"
                    data-bs-placement="bottom" data-bs-toggle="tooltip"
                    type="button" title="' . $packageName . $info . '">
                </i>';
	}
	
	// Get the listing link
	if (!is_null($post) && isset($post->country_code, $post->title)) {
		$url = dmUrl($post->country_code, urlGen()->postPath($post));
		$out = linkStrLimit($url, $post->title, 35, 'target="_blank"') . $paymentInfo;
	} else {
		$out = '--';
	}
	
	return $out;
}

/**
 * @param $entry
 * @param bool $withLink
 * @return string
 */
function getCountryFlag($entry, bool $withLink = false): string
{
	$out = '';
	
	$country = $entry->country ?? null;
	$countryCode = $country->code ?? $entry->country_code ?? null;
	
	if (!empty($countryCode)) {
		$countryName = $country->name ?? $countryCode;
		$countryFlagUrl = $country->flag_url ?? $entry->country_flag_url ?? null;
		
		if (!empty($countryFlagUrl)) {
			$out = ($withLink) ? '<a href="' . dmUrl($countryCode, '/', true, true) . '" target="_blank">' : '';
			$out .= '<img src="' . $countryFlagUrl . '" data-bs-toggle="tooltip" title="' . $countryName . '">';
			$out .= ($withLink) ? '</a>' : '';
			$out .= ' ';
		} else {
			$out .= $countryCode . ' ';
		}
	}
	
	return $out;
}

/**
 * Check if the Listing is verified
 *
 * @param $post
 * @return bool
 */
function isVerifiedPost($post): bool
{
	$post = (is_array($post)) ? Arr::toObject($post) : $post;
	
	if (
		!Arr::keyExists('email_verified_at', $post)
		|| !Arr::keyExists('phone_verified_at', $post)
		|| !Arr::keyExists('reviewed_at', $post)
	) {
		return false;
	}
	
	if (config('settings.listing_form.listings_review_activation')) {
		$verified = (!empty($post->email_verified_at) && !empty($post->phone_verified_at) && !empty($post->reviewed_at));
	} else {
		$verified = (!empty($post->email_verified_at) && !empty($post->phone_verified_at));
	}
	
	return $verified;
}

/**
 * Check if the User is verified
 *
 * @param $user
 * @return bool
 */
function isVerifiedUser($user): bool
{
	$user = (is_array($user)) ? Arr::toObject($user) : $user;
	
	if (!Arr::keyExists('email_verified_at', $user) || !Arr::keyExists('phone_verified_at', $user)) {
		return false;
	}
	
	return (!empty($user->email_verified_at) && !empty($user->phone_verified_at));
}

/**
 * @return bool
 */
function userHasSuperAdminPermissions(): bool
{
	if (auth()->check()) {
		$permissions = Permission::getSuperAdminPermissions();
		
		// Remove the standard admin permission
		$permissions = collect($permissions)
			->reject(fn ($item) => in_array($item, Permission::getStaffPermissions()))
			->toArray();
		
		// Check if user has the super admin permissions
		if (doesUserHavePermission(auth()->user(), $permissions)) {
			return true;
		}
	}
	
	return false;
}

/**
 * @param $entry
 * @param $key
 * @param $allEntries
 * @param $xPanel
 * @return string Returns the HTML string
 */
function sortableTreeElement($entry, $key, $allEntries, $xPanel): string
{
	$html = '';
	
	if (!isset($entry->treeElementShown)) {
		// Mark the element as shown
		$allEntries[$key]->treeElementShown = true;
		$entry->treeElementShown = true;
		
		// Build the tree element HTML
		$html .= '<li class="tab-pane" id="list_' . $entry->getKey() . '">';
		
		$dragIcon = '<i class="bi bi-arrows-move me-2"></i>';
		if (str_contains($xPanel->reorderLabel, '.')) {
			$tmp = explode('.', $xPanel->reorderLabel);
			$relation = head($tmp);
			$reorderLabel = last($tmp);
			$html .= '<div>' . $dragIcon . $entry->{$relation}->{$reorderLabel} . '</div>';
		} else {
			$html .= '<div>' . $dragIcon . $entry->{$xPanel->reorderLabel} . '</div>';
		}
		
		// See if this element has any children
		$children = [];
		foreach ($allEntries as $subEntry) {
			if ($subEntry->parent_id == $entry->getKey()) {
				$children[] = $subEntry;
			}
		}
		
		$children = collect($children)->sortBy('lft');
		
		// If it does have children, show them
		if ($children->count() > 0) {
			$html .= '<ol>';
			foreach ($children as $child) {
				$html .= sortableTreeElement($child, $child->getKey(), $allEntries, $xPanel);
			}
			$html .= '</ol>';
		}
		$html .= '</li>';
	}
	
	return $html;
}
