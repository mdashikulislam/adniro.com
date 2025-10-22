@extends('errors.master')

@php
	// Get page error title
	$defaultTitle = 'Page not found';
	$titleKey = 'error_http_404_title';
	$titleNamespace = "global.{$titleKey}";
	$title = trans()->has($titleNamespace) ? trans($titleNamespace) : $defaultTitle;
	
	// Get page error message
	$defaultMessageKey = 'error_http_404_message';
	$message = (isset($exception) && $exception instanceof \Throwable)
		? $exception->getMessage()
		: $defaultMessageKey;
	
	$defaultMessageKey = 'error_http_404_message';
	$defaultMessageNamespace = "global.{$defaultMessageKey}";
	$defaultMessage = trans()->has($defaultMessageNamespace)
		? trans($defaultMessageNamespace, ['url' => url('/')])
		: $title;
	
	if (isset($exception) && $exception instanceof \Throwable) {
		$badCharacters = ['[', ']', '\\', '/'];
		
		$message = $exception->getMessage();
		$message = str_replace(base_path(), '', $message);
		$message = !str($message)->contains($badCharacters) ? $message : $defaultMessage;
	} else {
		$message = $defaultMessage;
	}
@endphp

@section('title', $title)
@section('status', 404)
@section('message')
	{!! $message !!}
@endsection
