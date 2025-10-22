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

namespace App\Observers;

use App\Models\Advertising;

class AdvertisingObserver extends BaseObserver
{
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Advertising $advertising
	 * @return void
	 */
	public function saved(Advertising $advertising)
	{
		// ...
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Advertising $advertising
	 * @return void
	 */
	public function deleted(Advertising $advertising)
	{
		// ...
	}
}
