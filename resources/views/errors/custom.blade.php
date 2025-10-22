@extends('errors.master')

@php
	$httpStatus = getCachedReferrerList('http-status');
	
	$statusCode = (!empty($status) && !empty($httpStatus[$status])) ? $status : 500;
	if (isset($exception) && $exception instanceof \Throwable) {
		if (method_exists($exception, 'getStatusCode')) {
			try {
				$statusCode = $exception->getStatusCode();
			} catch (\Throwable $e) {
			}
		}
	}
	$title = $httpStatus[$statusCode] ?? 'Internal Server Error';
	
	// Message
	$defaultErrorMessage = 'An internal server error has occurred.';
	$extractedMessage = null;
	
	if (!empty($message)) {
		$extractedMessage = nl2br($message);
	}
	if (empty($extractedMessage)) {
		if (isset($exception) && $exception instanceof \Throwable) {
			$extractedMessage = $exception->getMessage();
			$extractedMessage = str_replace(base_path(), '', $extractedMessage);
			
			if (!empty($extractedMessage)) {
				if (method_exists($exception, 'getFile')) {
					$filePath = $exception->getFile();
					$filePath = str_replace(base_path(), '', $filePath);
					$extractedMessage .= "\n" . 'In the: <code>' . $filePath . '</code> file';
					if (method_exists($exception, 'getLine')) {
						$extractedMessage .= ' at line: <code>' . $exception->getLine() . '</code>';
					}
				}
				$extractedMessage = nl2br($extractedMessage);
			}
		}
	}
	
	$message = !empty($extractedMessage) ? $extractedMessage : $defaultErrorMessage;
	
	$allowedTags = "<br><strong><span>";
	$message = strip_tags($message, $allowedTags);
@endphp

@section('title', $title)
@section('status', $statusCode)
@section('message')
	{!! $message !!}
@endsection
