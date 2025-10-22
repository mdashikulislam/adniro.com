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

namespace App\Observers\Traits;

use App\Helpers\Common\JsonUtils;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;

/**
 * A trait for observer classes to handle JSON column operations in Eloquent models.
 *
 * This trait provides methods to detect changes in JSON column values and manage
 * associated file deletions, ensuring safe and efficient handling of JSON data in
 * Laravel observers. It is designed for use in observer base classes to standardize
 * JSON column interactions, such as checking for value changes at specific JSON paths
 * and removing outdated files from the filesystem when those values are updated.
 */
trait HasJsonColumn
{
	/**
	 * Delete a file referenced in a JSON column path if its value has changed.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @param string $column
	 * @param string $path Dots separated path
	 * @param \Illuminate\Contracts\Filesystem\Filesystem $filesystem
	 * @param string|null $protectedPath
	 * @param array|null $original
	 */
	protected function deleteJsonPathFile(
		Model      $model,
		string     $column,
		string     $path,
		Filesystem $filesystem,
		?string    $protectedPath = null,
		?array     $original = null
	): void
	{
		$original ??= $model->getOriginal();
		
		if (!$this->hasJsonPathChanged($model, $column, $path, $original)) {
			return; // nothing changed
		}
		
		$originalArrayColumnValue = $original[$column] ?? [];
		if (JsonUtils::isJson($originalArrayColumnValue)) {
			$originalArrayColumnValue = JsonUtils::jsonToArray($originalArrayColumnValue);
		}
		
		$originalPathValue = data_get($originalArrayColumnValue, $path);
		
		if (
			!empty($originalPathValue)
			&& (!empty($protectedPath) && !str_contains($originalPathValue, $protectedPath))
			&& $filesystem->exists($originalPathValue)
		) {
			$filesystem->delete($originalPathValue);
		}
	}
	
	/**
	 * Determine if a JSON column path's value has changed in the model.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @param string $column
	 * @param string $path Dots separated path
	 * @param array|null $original
	 * @return bool
	 */
	private function hasJsonPathChanged(Model $model, string $column, string $path, ?array $original = null): bool
	{
		$original ??= $model->getOriginal();
		
		$arrayColumnValue = $model->{$column} ?? [];
		$originalArrayColumnValue = $original[$column] ?? [];
		
		if (JsonUtils::isJson($arrayColumnValue)) {
			$arrayColumnValue = JsonUtils::jsonToArray($arrayColumnValue);
		}
		if (JsonUtils::isJson($originalArrayColumnValue)) {
			$originalArrayColumnValue = JsonUtils::jsonToArray($originalArrayColumnValue);
		}
		
		$pathValue = data_get($arrayColumnValue, $path);
		$originalPathValue = data_get($originalArrayColumnValue, $path);
		
		return ($pathValue != $originalPathValue);
	}
}
