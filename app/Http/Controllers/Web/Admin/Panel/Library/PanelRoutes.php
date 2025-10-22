<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library;

use Illuminate\Support\Facades\Route;

class PanelRoutes
{
	/**
	 * Register a resource route with additional custom routes for admin panel
	 *
	 * Creates both standard Laravel resource routes and custom admin-specific routes
	 * such as search, reorder, bulk actions, and revision management.
	 *
	 * @param string $name
	 * @param string $controller
	 * @param array $options
	 * @return void
	 */
	public static function resource(string $name, string $controller, array $options = []): void
	{
		// Generate route names prefix (e.g., 'admin.users.' or 'admin.categories.sub-categories.')
		$namesPrefix = self::normalizeRouteNamesPrefix($name);
		$namesPrefix = 'admin.' . $namesPrefix . '.';
		
		$uri = $name;
		
		// Handle nested resources (e.g., 'categories.subcategories')
		// Info: https://laravel.com/docs/12.x/controllers#restful-nested-resources
		if (str_contains($name, '.')) {
			$parameters = self::customizedResourceParameters($name);
			$options['parameters'] = $options['parameters'] ?? $parameters;
			
			$uri = self::normalizeRouteUri($name, $options);
		}
		
		// Register custom admin panel routes
		Route::post($uri . '/search', $controller . '@search')->name($namesPrefix . 'search');
		Route::get($uri . '/reorder/{lang?}', $controller . '@reorder')->name($namesPrefix . 'reorder');
		Route::post($uri . '/reorder/{lang?}', $controller . '@saveReorder')->name($namesPrefix . 'saveReorder');
		Route::get($uri . '/{id}/details', $controller . '@showDetailsRow')->name($namesPrefix . 'showDetailsRow');
		Route::get($uri . '/{id}/revisions', $controller . '@listRevisions')->name($namesPrefix . 'listRevisions');
		Route::post($uri . '/{id}/revisions/{revisionId}/restore', $controller . '@restoreRevision')->name($namesPrefix . 'restoreRevision');
		Route::post($uri . '/bulk_actions', $controller . '@bulkActions')->name($namesPrefix . 'bulkActions');
		
		// Register standard CRUD routes with custom naming convention
		$optionsWithDefaultRouteNames = array_merge([
			'names' => [
				'index'   => $namesPrefix . 'index',
				'create'  => $namesPrefix . 'create',
				'store'   => $namesPrefix . 'store',
				'edit'    => $namesPrefix . 'edit',
				'update'  => $namesPrefix . 'update',
				'show'    => $namesPrefix . 'show',
				'destroy' => $namesPrefix . 'destroy',
			],
		], $options);
		
		Route::resource($name, $controller, $optionsWithDefaultRouteNames);
	}
	
	/**
	 * Normalize route names prefix for consistent naming convention
	 *
	 * Converts route paths to dot-separated naming convention suitable for Laravel route names.
	 * e.g. Convert "entities/{entityId}/sub_entities" to "entities.sub-entities"
	 *
	 * @param string $path
	 * @return string
	 */
	private static function normalizeRouteNamesPrefix(string $path): string
	{
		// Replace slashes with dots and convert underscores to hyphens
		$path = str_replace(['/', '_'], ['.', '-'], $path);
		
		// Remove route parameter placeholders (e.g., {catId}, {userId})
		$path = preg_replace('/\{[^}]*}/', '', $path);
		
		// Remove any double dots or trailing/leading dots
		$path = preg_replace('/\.+/', '.', $path);
		
		return trim($path, '.');
	}
	
	/**
	 * Generate customized resource parameters for nested routes
	 *
	 * Creates parameter mappings for nested resources, converting segment names
	 * to appropriate parameter names.
	 *
	 * Note: If nested resources (e.g., 'categories.subcategories') is used for resource routes,
	 * and if this method is not called to customize route resource parameters, we need to update
	 * all the admin controller request()->route() parameters keys.
	 * Simple singular keys is allowed by Laravel by default.
	 *
	 * Info: https://laravel.com/docs/12.x/controllers#restful-nested-resources
	 *
	 * @param string $name
	 * @return array
	 */
	private static function customizedResourceParameters(string $name): array
	{
		// Remove existing parameter placeholders and clean up slashes
		$name = preg_replace('/\{[^}]*}/', '', $name);
		$name = preg_replace('/\/+/', '/', $name);
		
		// Split the path into segments
		$segments = preg_split('/[.\/]/', $name);
		$lastKey = array_key_last($segments);
		
		return collect($segments)
			->mapWithKeys(function ($segment, $key) use ($lastKey) {
				$singularParam = str($segment)->singular();
				$customizedParam = $singularParam->camel()->append('Id');
				
				$param = ($key === $lastKey) ? $singularParam : $customizedParam;
				
				return [$segment => $param->toString()];
			})->toArray();
	}
	
	/**
	 * Normalize route URI prefix for custom routes
	 *
	 * Converts dot-separated route names to proper URI paths with parameters.
	 * Example: "categories.subcategories" with parameters becomes "categories/{categoryId}/subcategories"
	 *
	 * @param string $name
	 * @param array $options
	 * @return string
	 */
	private static function normalizeRouteUri(string $name, array $options = []): string
	{
		$parameters = $options['parameters'] ?? [];
		if (empty($parameters)) return $name;
		
		// If no dots in name, return as-is (simple resource)
		if (!str_contains($name, '.')) return $name;
		
		// Split by dots to process each segment
		$array = explode('.', $name);
		$lastKey = array_key_last($array);
		
		return collect($array)
			->map(function ($item, $key) use ($parameters, $lastKey) {
				$paramName = $parameters[$item] ?? null;
				
				// Add parameter placeholder for all segments except the last one
				$paramName = ($key !== $lastKey)
					? (!empty($paramName) ? '/{' . $paramName . '}' : '')
					: '';
				
				return $item . $paramName;
			})->implode('/');
	}
}
