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

namespace App\Models\Traits;

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait PaymentMethodTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function crudDisplayNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = $this->display_name ?? '--';
		if (!empty($this->name)) {
			$out = $out . plugin_demo_info($this->name);
		}
		
		return $out;
	}
	
	public function crudCountriesColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = strtoupper(trans('admin.All'));
		if (!empty($this->countries)) {
			$countriesCropped = str($this->countries)->limit(50, ' [...]');
			$out = '<div title="' . $this->countries . '">' . $countriesCropped . '</div>';
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
