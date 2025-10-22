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

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait HasActiveColumn
{
	/**
	 * @param \App\Http\Controllers\Web\Admin\Panel\Library\Panel|null $xPanel
	 * @param array $column
	 * @return string|null
	 */
    public function crudActiveColumn(?Panel $xPanel = null, array $column = []): ?string
    {
        if (!in_array('active', $this->getFillable())) return null;
		$active = $this->active ?? false;
        
        return ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'active', $active);
    }
}
