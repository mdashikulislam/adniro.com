@extends('errors.master')

@php
	// adniro: error details only while debugging, never for visitors
	$title = t('Internal Server Error');
	$message = \App\Helpers\Common\ErrorPageMessage::forServerError(
		(isset($exception) && $exception instanceof \Throwable) ? $exception : null,
		e(t('An internal server error has occurred')),
		(bool)config('app.debug')
	);
@endphp

@section('title', $title)
@section('status', 500)
@section('message')
	{!! $message !!}
@endsection
