@extends('errors.master')

@php
	$title = t('Internal Server Error');
	
	$isDebugEnabled = config('app.debug');
	$defaultErrorMessage = t('An internal server error has occurred');
	$extractedMessage = null;
	
	if (isset($exception) && $exception instanceof \Throwable) {
		$extractedMessage = $exception->getMessage();
		$extractedMessage = str_replace(base_path(), '', $extractedMessage);
		
		if (!empty($extractedMessage) && $isDebugEnabled) {
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
	
	$message = !empty($extractedMessage) ? $extractedMessage : $defaultErrorMessage;
@endphp

@section('title', $title)
@section('status', 401)
@section('message')
	{!! $message !!}
@endsection
