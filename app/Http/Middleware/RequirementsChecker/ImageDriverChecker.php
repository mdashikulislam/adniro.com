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

namespace App\Http\Middleware\RequirementsChecker;

trait ImageDriverChecker
{
	/**
	 * Get eventual error message for image driver requirements.
	 *
	 * @return string|null
	 */
	protected function getImageDriverErrors(): ?string
	{
		$driver = config('image.driver');
		
		// Check if image driver is configured in the environment
		if (empty($driver) || !is_string($driver)) {
			$error = "Image manipulation driver is not configured. Please set the IMAGE_DRIVER environment variable in your .env file.";
			$error .= "\n\nExample: IMAGE_DRIVER=gd";
			$error .= "\n\nSupported options:";
			$error .= "\n- gd (PHP GD extension)";
			$error .= "\n- imagick (PHP Imagick extension)";
			
			return $error;
		}
		
		$supportedDrivers = $this->getSupportedImageDrivers();
		
		// Validate that the configured driver is supported by the application
		if (empty($supportedDrivers[$driver])) {
			$error = "Unsupported image manipulation driver: '{$driver}'";
			$error .= "\n\nThe configured driver is not recognized by the application.";
			$error .= "\n\nSupported drivers:";
			
			foreach ($supportedDrivers as $shortName) {
				$error .= "\n- {$shortName}";
			}
			
			$error .= "\n\nPlease check your .env file and ensure:";
			$error .= "\n1. The IMAGE_DRIVER value is spelled correctly";
			$error .= "\n2. There are no extra spaces before or after the value";
			
			return $error;
		}
		
		// Validate that the driver's system requirements are met
		$driverType = $supportedDrivers[$driver];
		
		if ($driverType === 'imagick') {
			return $this->validateImagickDriverRequirements();
		} else if ($driverType === 'gd') {
			return $this->validateGdDriverRequirements();
		}
		
		return null;
	}
	
	/**
	 * Check if Imagick driver requirements are met on the server.
	 *
	 * @return string|null
	 */
	private function validateImagickDriverRequirements(): ?string
	{
		$imagickLoaded = extension_loaded('imagick');
		$imagickClassExists = class_exists('\Imagick');
		
		if (!($imagickLoaded && $imagickClassExists)) {
			$error = "Imagick driver configuration error";
			$error .= "\n\nThe Imagick image manipulation driver is configured but not properly installed on this server.";
			
			if (!$imagickLoaded) {
				$error .= "\n\n❌ PHP Imagick extension is not loaded";
			} else {
				$error .= "\n\n✅ PHP Imagick extension is loaded";
			}
			
			if (!$imagickClassExists) {
				$error .= "\n❌ Imagick class is not available";
			} else {
				$error .= "\n✅ Imagick class is available";
			}
			
			$error .= "\n\nTo fix this issue:";
			$error .= "\n1. Install the PHP Imagick extension on your server";
			$error .= "\n2. Ensure it's enabled in your php.ini file";
			$error .= "\n3. Restart your web server";
			$error .= "\n4. Or switch to the GD driver by setting IMAGE_DRIVER=gd in your .env file";
			
			return $error;
		}
		
		return null;
	}
	
	/**
	 * Check if GD driver requirements are met on the server.
	 *
	 * @return string|null
	 */
	private function validateGdDriverRequirements(): ?string
	{
		$gdLoaded = extension_loaded('gd');
		$gdFunctionExists = function_exists('gd_info');
		
		if (!($gdLoaded && $gdFunctionExists)) {
			$error = "GD driver configuration error";
			$error .= "\n\nThe GD image manipulation driver is configured but not properly installed on this server.";
			
			if (!$gdLoaded) {
				$error .= "\n\n❌ PHP GD extension is not loaded";
			} else {
				$error .= "\n\n✅ PHP GD extension is loaded";
			}
			
			if (!$gdFunctionExists) {
				$error .= "\n❌ GD functions are not available";
			} else {
				$error .= "\n✅ GD functions are available";
			}
			
			$error .= "\n\nTo fix this issue:";
			$error .= "\n1. Install the PHP GD extension on your server";
			$error .= "\n2. Ensure it's enabled in your php.ini file (uncomment extension=gd)";
			$error .= "\n3. Restart your web server";
			$error .= "\n4. Or switch to the Imagick driver if available by setting IMAGE_DRIVER=imagick in your .env file";
			
			return $error;
		}
		
		return null;
	}
	
	/**
	 * Get the list of supported image drivers.
	 *
	 * @return array
	 */
	private function getSupportedImageDrivers(): array
	{
		return [
			\Intervention\Image\Drivers\Gd\Driver::class      => 'gd',
			\Intervention\Image\Drivers\Imagick\Driver::class => 'imagick',
		];
	}
}
