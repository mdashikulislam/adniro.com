@extends('errors.master')

@php
	// adniro: visitors see a message given on purpose (escaped), never framework text
	$title = t('Forbidden');
	$message = \App\Helpers\Common\ErrorPageMessage::forVisitor(
		(isset($exception) && $exception instanceof \Throwable) ? $exception : null,
		t('Meanwhile, you may return to homepage', ['url' => url('/')])
	);
@endphp

@section('title', $title)
@section('status', 403)
@section('message')
	{!! $message !!}
@endsection
