@extends('errors.master')

@php
    $title = t('Unauthorized action');
	
    $defaultErrorMessage = t('Meanwhile, you may return to homepage', ['url' => url('/')]);
    $extractedMessage = null;
    
    if (isset($exception) && $exception instanceof \Throwable) {
        $extractedMessage = $exception->getMessage();
        $extractedMessage = str_replace(base_path(), '', $extractedMessage);
    }
    
    $message = !empty($extractedMessage) ? $extractedMessage : $defaultErrorMessage;
@endphp

@section('title', $title)
@section('status', 401)
@section('message')
    {!! $message !!}
@endsection
