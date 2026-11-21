<?php

namespace App\Helpers\Common;

use Throwable;

/*
 * What an error page may tell a visitor (adniro).
 *
 * A message given on purpose, e.g. abort(403, 'Only the owner can do this.'), is shown escaped.
 * Messages written by the framework ("The route ... could not be found", "No query results for
 * model ...") and server error details are replaced by the page's default text, which is trusted
 * HTML from the translation files. Server error details show only while debugging.
 */

final class ErrorPageMessage
{
	/** Starts of messages the framework writes for developers, not visitors. */
	private const FRAMEWORK_MESSAGES = ['The route ', 'No query results for model', 'Target class ', 'Call to '];
	
	public static function forVisitor(?Throwable $exception, string $defaultHtml): string
	{
		$message = self::message($exception);
		if ($message === '' || self::isFrameworkMessage($message)) {
			return $defaultHtml;
		}
		
		return nl2br(e($message));
	}
	
	public static function forServerError(?Throwable $exception, string $defaultHtml, bool $debug): string
	{
		$message = self::message($exception);
		if (!$debug || $message === '') {
			return $defaultHtml;
		}
		
		$file = str_replace(base_path(), '', $exception->getFile());
		
		return nl2br(e($message)) . '<br>In the: <code>' . e($file) . '</code> file at line: <code>' . (int)$exception->getLine() . '</code>';
	}
	
	private static function message(?Throwable $exception): string
	{
		return $exception ? trim(str_replace(base_path(), '', $exception->getMessage())) : '';
	}
	
	private static function isFrameworkMessage(string $message): bool
	{
		foreach (self::FRAMEWORK_MESSAGES as $start) {
			if (str_starts_with($message, $start)) {
				return true;
			}
		}
		
		return false;
	}
}
