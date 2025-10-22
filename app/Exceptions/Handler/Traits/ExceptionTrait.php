<?php

namespace App\Exceptions\Handler\Traits;

use Illuminate\Support\Facades\View;

trait ExceptionTrait
{
	/**
	 * Get theme error view (dot-separated) path
	 *
	 * @param string|null $viewName
	 * @return string|null
	 */
	protected function getThemeErrorViewPath(?string $viewName = null): ?string
	{
		/*
		 * Set default theme errors views directory in the possible views base directories array
		 *
		 * IMPORTANT:
		 * - The custom "views/errors/" (i.e. errors) directory that auto-discovers errors views
		 *   by their status code should not be added as errors directory path below.
		 *
		 * DETAILS:
		 * - HTTP error views located in /resources/views/errors/ cannot be manually rendered using
		 *   response()->view() or view(). They are exclusively handled by Laravel’s core (even if their design is customized).
		 *
		 * - To manually render customized HTTP error views (e.g., 404, 503, etc.), place them in a different directory,
		 *   such as /resources/views/front/errors/. From there, they can be rendered using response()->view() or view().
		 *
		 * - Custom error views with non-standard names (e.g., custom.blade.php) work normally within /resources/views/errors/
		 *   These can be handled manually without restrictions since they don't conflict with Laravel's HTTP error handling conventions.
		 */
		$viewPathDirs = [
			'front.errors',
		];
		
		// Add Laravel's system error directory "views/errors/" only for non-HTTP error views (e.g., 'custom').
		if ($viewName == 'custom') {
			$viewPathDirs[] = 'errors';
		}
		
		/*
		 * Create a custom view namespace to ensure Laravel uses the theme's error directory instead
		 * of the default "resources/views/errors" directory. This allows us to reference error views with
		 * "theme::errors." rather than "errors.", avoiding potential confusion with the default view hint for "resources/views/errors".
		 *
		 * Next, prepend the theme's error views directory to the $viewPathDirs array.
		 */
		$themePath = base_path('extras/themes/customized/views');
		if (is_dir($themePath)) {
			View::addNamespace('customized', $themePath);
			array_unshift($viewPathDirs, 'customized::errors');
		}
		
		// Use the first view found
		$viewPath = null;
		foreach ($viewPathDirs as $viewPathDir) {
			$tmpViewPath = "{$viewPathDir}.{$viewName}";
			if (view()->exists($tmpViewPath)) {
				$viewPath = $tmpViewPath;
				break;
			}
		}
		
		return $viewPath;
	}
}
