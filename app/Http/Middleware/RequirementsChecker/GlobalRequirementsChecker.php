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

use App\Http\Controllers\Web\Setup\Install\Traits\CheckerTrait;

trait GlobalRequirementsChecker
{
	use CheckerTrait, ImageDriverChecker;
	
	/**
	 * Get eventual error message (due to lack of a requirement)
	 *
	 * @return string|null
	 */
	protected function getRequirementsErrors(): ?string
	{
		// Check global system requirements first (components & permissions)
		$globalRequirementsError = $this->getGlobalRequirementsErrors();
		if (!empty($globalRequirementsError)) {
			return $globalRequirementsError;
		}
		
		// Check image driver requirements
		$imageDriverError = $this->getImageDriverErrors();
		if (!empty($imageDriverError)) {
			return $this->formatErrorsMessages([$imageDriverError]);
		}
		
		return null;
	}
	
	/**
	 * Get eventual error message for global system requirements (components & permissions)
	 *
	 * @return string|null
	 */
	private function getGlobalRequirementsErrors(): ?string
	{
		// Get the system requirements (components & permissions)
		$requirements = array_merge($this->getComponents(), $this->getPermissions());
		if (empty($requirements)) {
			return null;
		}
		
		// Get eventual error message (due to lack of a requirement)
		$errorMessages = [];
		foreach ($requirements as $requirement) {
			if (
				!array_key_exists('permanentChecking', $requirement)
				|| !array_key_exists('required', $requirement)
				|| !array_key_exists('isOk', $requirement)
				|| !array_key_exists('name', $requirement)
			) {
				continue;
			}
			
			if ($requirement['permanentChecking'] && $requirement['required'] && !$requirement['isOk']) {
				$message = $requirement['warning'];
				
				// Customize the permissions errors message
				$anonymousDir = 'The directory';
				if (str_starts_with($message, $anonymousDir)) {
					$specificDir = $anonymousDir . ' <code>' . $requirement['name'] . '</code>';
					$message = str_replace($anonymousDir, $specificDir, $message);
				}
				
				$errorMessages[] = '- ' . $message;
			}
		}
		
		return $this->formatErrorsMessages($errorMessages);
	}
	
	/**
	 * @param array|null $errorMessages
	 * @return string|null
	 */
	protected function formatErrorsMessages(?array $errorMessages): ?string
	{
		if (empty($errorMessages)) return null;
		
		return implode("\n", $errorMessages);
	}
}
