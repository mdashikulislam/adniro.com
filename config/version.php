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

return [
	// PHP minimum version required
	'php' => '8.2',
	
	// Latest app's version
	'app' => '18.1.1',
	
	// Current app's version (i.e. App's version in the .env file)
	'env' => function_exists('env') ? env('APP_VERSION') : null,
	
	// Plugins minimum version required
	'compatibility' => [
		'adyen'            => '2.2.3',
		'cashfree'         => '2.2.4',
		'currencyexchange' => '4.3.5',
		'detectadsblocker' => '2.0.3',
		'domainmapping'    => '5.4.9',
		'flutterwave'      => '2.2.3',
		'iyzico'           => '2.2.5',
		'offlinepayment'   => '4.1.5',
		'paystack'         => '2.2.3',
		'payu'             => '3.2.3',
		'razorpay'         => '2.2.3',
		'reviews'          => '4.4.4',
		'stripe'           => '3.2.4',
		'twocheckout'      => '3.2.5',
		'watermark'        => '3.1.9',
	],
];
