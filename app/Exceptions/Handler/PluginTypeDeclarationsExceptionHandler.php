<?php

namespace App\Exceptions\Handler;

use App\Exceptions\Handler\Plugin\OutToDatePlugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * Check if there are no problems in a plugin code
 */

trait PluginTypeDeclarationsExceptionHandler
{
	use OutToDatePlugin;
	
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isPluginTypeDeclarationsException(\Throwable $e): bool
	{
		// Check if there are no problems in a plugin code
		return (
			method_exists($e, 'getFile') && method_exists($e, 'getMessage')
			&& !empty($e->getFile()) && !empty($e->getMessage())
			&& str_contains($e->getFile(), '/extras/plugins/')
			&& str_contains($e->getMessage(), 'extras\plugins\\')
			&& str_contains($e->getMessage(), 'must be compatible')
		);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return false|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
	 */
	protected function responsePluginTypeDeclarationsException(\Throwable $e, Request $request): Response|false|JsonResponse
	{
		$message = $this->getPluginTypeDeclarationsExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string|null
	 */
	private function getPluginTypeDeclarationsExceptionMessage(\Throwable $e, Request $request): ?string
	{
		$message = $e->getMessage();
		
		return !empty($message) ? $this->tryToArchivePlugin($message) : null;
	}
}
