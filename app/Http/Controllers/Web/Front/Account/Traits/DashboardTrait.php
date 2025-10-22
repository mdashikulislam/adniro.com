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

namespace App\Http\Controllers\Web\Front\Account\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait DashboardTrait
{
	/**
	 * @param $data
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	protected function handlePhotoData($data): JsonResponse|RedirectResponse
	{
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message');
		
		// AJAX Response
		if (isFromAjax()) {
			if (!data_get($data, 'success')) {
				$message = $message ?? t('unknown_error');
				
				return ajaxResponse()->json(['error' => $message], $status);
			}
			
			$fileInput = data_get($data, 'extra.fileInput');
			
			return ajaxResponse()->json($fileInput);
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
			
			return redirect()->to(urlGen()->accountOverview());
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->to(urlGen()->accountOverview())->withInput();
		}
	}
}
