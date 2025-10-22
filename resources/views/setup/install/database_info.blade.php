{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
--}}
@extends('setup.install.layouts.master')
@section('title', trans('messages.database_info_title'))

@php
    // Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
    $formActionUrl ??= request()->fullUrl();
    $nextStepUrl ??= url('/');
    $nextStepLabel ??= trans('messages.next');
@endphp
@section('content')
    <form name="databaseInfoForm" action="{{ $formActionUrl }}" method="POST" novalidate>
        @csrf
        
        <div class="row">
            <div class="mb-4 col-md-12">
                <h5 class="mb-0 fs-5 border-bottom pb-3">
                    <i class="bi bi-database"></i> {{ trans('messages.database_info_title') }}
                </h5>
            </div>
            
            {{-- host --}}
            @php
                $hostHintParam = ['socket' => mb_strtolower(trans('messages.database_socket'))];
            @endphp
            @include('helpers.forms.fields.text', [
				'label'     => trans('messages.database_host'),
				'name'      => 'host',
				'required'  => true,
				'value'     => $databaseInfo['host'] ?? null,
				'hint'      => trans('messages.database_host_hint', $hostHintParam),
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
			])
            
            {{-- port --}}
            @php
                $portHintParam = ['socket' => mb_strtolower(trans('messages.database_socket'))];
            @endphp
            @include('helpers.forms.fields.text', [
				'label'       => trans('messages.database_port'),
				'name'        => 'port',
				'required'    => true,
				'value'       => $databaseInfo['port'] ?? null,
				'default'     => 3306,
				'placeholder' => '3306',
				'hint'        => trans('messages.database_port_hint', $portHintParam),
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
            
            {{-- socket --}}
            @php
                $socketHintParam = [
                    'host' => mb_strtolower(trans('messages.database_host')),
                    'port' => mb_strtolower(trans('messages.database_port'))
                ];
            @endphp
            @include('helpers.forms.fields.text', [
				'label'     => trans('messages.database_socket') . trans('messages.optional'),
				'name'      => 'socket',
				'required'  => false,
				'value'     => $databaseInfo['socket'] ?? null,
				'hint'      => trans('messages.database_socket_hint', $socketHintParam),
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
				'newline'   => true,
			])
            
            {{-- connection --}}
            {{--
            @php
                $connectionOptions = [
                    ['value' => 'mysql', 'text' => trans('messages.mysql')],
                    ['value' => 'mariadb', 'text' => trans('messages.mariadb')],
                ];
            @endphp
            @include('helpers.forms.fields.select2', [
				'label'     => trans('messages.database_driver'),
				'name'      => 'connection',
				'required'  => false,
				'options'   => $connectionOptions,
				'value'     => $databaseInfo['connection'] ?? null,
				'hint'      => trans('messages.database_driver_hint'),
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
			])
            --}}
            
            {{-- database --}}
            @include('helpers.forms.fields.text', [
				'label'     => trans('messages.database_name'),
				'name'      => 'database',
				'required'  => true,
				'value'     => $databaseInfo['database'] ?? null,
				'hint'      => 'The database where the tables will be created',
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
			])
            
            {{-- prefix --}}
            @php
                $itemSlug = config('larapen.core.item.slug');
                $prefixPlaceholder = generateRandomString(length: 3, type: 'alpha') . '_';
				$prefixPlaceholder = ($itemSlug == 'laraclassifier') ? 'lc_' : $prefixPlaceholder;
				$prefixPlaceholder = ($itemSlug == 'jobclass') ? 'jc_' : $prefixPlaceholder;
            @endphp
            @include('helpers.forms.fields.text', [
				'label'       => trans('messages.database_tables_prefix') . trans('messages.optional'),
				'name'        => 'prefix',
				'required'    => false,
				'value'       => $databaseInfo['prefix'] ?? null,
				'default'     => $prefixPlaceholder,
				'placeholder' => 'e.g. ' . $prefixPlaceholder,
				'hint'        => trans('messages.database_tables_prefix_hint'),
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
            
            {{-- username --}}
            @include('helpers.forms.fields.text', [
				'label'     => trans('messages.database_username'),
				'name'      => 'username',
				'required'  => true,
				'value'     => $databaseInfo['username'] ?? null,
				'hint'      => 'The database user',
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
			])
            
            {{-- password --}}
            @include('helpers.forms.fields.text', [
				'label'     => trans('messages.database_password'),
				'name'      => 'password',
				'required'  => false,
				'value'     => $databaseInfo['password'] ?? null,
				'hint'      => 'The database user\'s password',
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
			])
            
            <div class="col-md-12 text-end border-top pt-3 mt-3">
                <button type="submit" class="btn btn-primary">
                    {!! $nextStepLabel !!} <i class="bi bi-plugin"></i>
                </button>
            </div>
        </div>
    </form>
    
@endsection

@section('after_scripts')
@endsection
