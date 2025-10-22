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

trait CurrencyTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = $xPanel->getUrl($this->getKey() . '/edit');
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function crudSymbolColumn(?Panel $xPanel = null, array $column = []): string
	{
		return html_entity_decode($this->symbol);
	}
	
	public function crudPositionColumn(?Panel $xPanel = null, array $column = []): string
	{
		$toggleIcon = ($this->in_left == 1)
			? 'fa-solid fa-toggle-on'
			: 'fa-solid fa-toggle-off';
		
		return '<i class="admin-single-icon ' . $toggleIcon . '" aria-hidden="true"></i>';
	}
	
	public function crudRateColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = '-';
		
		if (!empty($this->rate)) {
			$driver = config('currencyexchange.default');
			$currencyBase = config('currencyexchange.drivers.' . $driver . '.currencyBase', 'XXX');
			$info = '1 ' . $currencyBase . ' =';
			
			$tooltip = ' data-bs-toggle="tooltip" title="' . $info . '"';
			
			$out = '<span' . $tooltip . '>' . $this->rate . '</span>';
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
