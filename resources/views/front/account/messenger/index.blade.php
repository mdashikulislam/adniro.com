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
@extends('front.layouts.master')

@php
    $apiResult ??= [];
	$threads = (array)data_get($apiResult, 'data');
	$totalThreads = (int)data_get($apiResult, 'meta.total', 0);
@endphp

@section('content')
	@include('front.common.spacer')
    <div class="main-container">
        <div class="container">
            <div class="row">
                
                <div class="col-md-3">
                    @include('front.account.partials.sidebar')
                </div>
                
                <div class="col-md-9">
                    <div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2">
                        <h3 class="fw-bold border-bottom pb-3 mb-4">
                            <i class="bi bi-chat-text"></i> {{ t('inbox') }}
                        </h3>
                        
                        <div id="successMsg" class="alert alert-success d-none" role="alert"></div>
                        <div id="errorMsg" class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="">
                            <div class="row mb-3">
                                @csrf
                                
                                <div class="col-md-3 col-lg-2">
                                    <div class="btn-group d-md-inline-block d-sm-none d-none"></div>
                                </div>
                                
                                <div class="col-md-9 col-lg-10 d-flex justify-content-between">
                                    <div class="btn-group d-md-none d-sm-inline-block">
                                        <a href="#" class="btn btn-primary text-uppercase">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="d-md-inline-block d-sm-none d-none">
                                        <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                            <button type="button" class="btn btn-outline-primary">
                                                <input type="checkbox" id="form-check-all">
                                            </button>
                                            
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="dropdown-menu-sort-selected">{{ t('action') }}</span>
                                                </button>
                                                <ul id="groupedAction" class="dropdown-menu dropdown-menu-sort">
                                                    <li>
                                                        <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/actions?type=markAsRead') }}"
                                                           class="dropdown-item"
                                                        >
                                                            {{  t('Mark as read') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/actions?type=markAsUnread') }}"
                                                           class="dropdown-item"
                                                        >
                                                            {{ t('Mark as unread') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/actions?type=markAsImportant') }}"
                                                           class="dropdown-item"
                                                        >
                                                            {{ t('Mark as important') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/actions?type=markAsNotImportant') }}"
                                                           class="dropdown-item"
                                                        >
                                                            {{ t('Mark as not important') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/delete') }}"
                                                           class="dropdown-item"
                                                        >
                                                            {{ t('Delete') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            
                                            <button type="button"
                                                    id="btnRefresh"
                                                    class="btn btn-outline-primary"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ t('refresh') }}"
                                            >
                                                <span class="fa-solid fa-rotate"></span>
                                            </button>
                                            
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ t('more') }}
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a href="" class="dropdown-item markAllAsRead">{{ t('Mark all as read') }}</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="message-tool-bar-right d-flex align-items-center" id="linksThreads">
                                        @include('front.account.messenger.threads.links')
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                @include('front.account.messenger.partials.sidebar')
                                
                                <div class="col-md-9 col-lg-10 message-list">
                                    <div class="container border rounded bg-body py-2" id="listThreads">
                                        @include('front.account.messenger.threads.threads')
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
	<script>
        var loadingImage = '{{ url('images/spinners/fading-line.gif') }}';
        var loadingErrorMessage = '{{ t('Threads could not be loaded') }}';
        var actionText = '{{ t('action') }}';
        var actionErrorMessage = '{{ t('This action could not be done') }}';
        var title = {
            'seen': '{{ t('Mark as read') }}',
            'notSeen': '{{ t('Mark as unread') }}',
            'important': '{{ t('Mark as important') }}',
            'notImportant': '{{ t('Mark as not important') }}',
        };
	</script>
    <script src="{{ url('assets/js/app/messenger.js') }}" type="text/javascript"></script>
@endsection
