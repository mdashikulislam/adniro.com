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

namespace App\Services\Auth\App\Helpers\SocialLogin;

use App\Enums\EnumToArray;

enum SocialLoginButton: string
{
	use EnumToArray;
	
	case LogoOnly = 'logoOnly';
	case Default = 'default';
	case LoginWithDefault = 'loginWithDefault';
	
	public function label(): string
	{
		return match ($this) {
			self::LogoOnly => trans('auth.social_media_name_logo'),
			self::Default => trans('auth.social_media_name_default'),
			self::LoginWithDefault => trans('auth.social_media_name_login_with'),
		};
	}
}
