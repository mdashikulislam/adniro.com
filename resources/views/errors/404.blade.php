@extends('errors.master')

@php
	// adniro: visitors see a message given on purpose (escaped), never framework text
	$titleKey = 'global.error_http_404_title';
	$title = trans()->has($titleKey) ? trans($titleKey) : 'Page not found';
	
	$defaultKey = 'global.error_http_404_message';
	$message = \App\Helpers\Common\ErrorPageMessage::forVisitor(
		(isset($exception) && $exception instanceof \Throwable) ? $exception : null,
		trans()->has($defaultKey) ? trans($defaultKey, ['url' => url('/')]) : e($title)
	);
	$showSearch = true;
@endphp

@section('title', $title)
@section('status', 404)
@section('message')
	{!! $message !!}
@endsection
