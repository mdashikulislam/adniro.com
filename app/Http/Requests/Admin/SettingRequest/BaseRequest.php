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

namespace App\Http\Requests\Admin\SettingRequest;

use App\Http\Requests\Admin\Request;

class BaseRequest extends Request
{
	/**
	 * @param array $messages
	 * @return array
	 */
	protected function mergeMessages(array $messages = []): array
	{
		return array_merge(parent::messages(), $messages);
	}
	
	/**
	 * @param array $attributes
	 * @return array
	 */
	protected function mergeAttributes(array $attributes = []): array
	{
		return array_merge(parent::attributes(), $attributes);
	}
}
