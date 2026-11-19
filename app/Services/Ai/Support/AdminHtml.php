<?php

namespace App\Services\Ai\Support;

use Carbon\CarbonInterface;

/*
 * Small HTML fragments for the AI admin screens (adniro:ai).
 */

class AdminHtml
{
	/**
	 * Status badge: "Off", "Not tested", "Working" or "Failed" (with the error as a tooltip).
	 */
	public static function statusBadge(bool $active, ?bool $testOk, ?CarbonInterface $testedAt, ?string $error): string
	{
		if (!$active) {
			return '<span class="badge bg-secondary">' . e(trans('ai.status_off')) . '</span>';
		}
		
		if (is_null($testOk)) {
			return '<span class="badge bg-warning text-dark">' . e(trans('ai.status_untested')) . '</span>';
		}
		
		$when = $testedAt ? $testedAt->diffForHumans() : '';
		
		if ($testOk) {
			return '<span class="badge bg-success" title="' . e($when) . '">' . e(trans('ai.status_working')) . '</span>';
		}
		
		$title = trim($when . ' ' . (string)$error);
		
		return '<span class="badge bg-danger" title="' . e($title) . '">' . e(trans('ai.status_failed')) . '</span>'
			. (!empty($error) ? '<br><small class="text-danger">' . e(str($error)->limit(90)) . '</small>' : '');
	}
	
	/**
	 * A one-button POST form, so actions that call an AI provider are never triggered by a plain link.
	 */
	public static function postButton(string $url, string $label, string $icon): string
	{
		return '<form method="POST" action="' . e($url) . '" class="d-inline">'
			. '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
			. '<button type="submit" class="btn btn-xs btn-light"><i class="' . e($icon) . '"></i> ' . e($label) . '</button>'
			. '</form>';
	}
}
