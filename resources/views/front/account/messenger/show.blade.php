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
    $authUser = auth()->check() ? auth()->user() : null;
	$authUserId = !empty($authUser) ? $authUser->getAuthIdentifier() : 0;
	
	$thread ??= [];
	$threadId = data_get($thread, 'id', 0);
	
    $fiTheme = config('larapen.core.fileinput.theme', 'bs5');
	$allowedFileFormatsJson = collect(getAllowedFileFormats())->toJson();
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
                        <h2 class="fw-bold border-bottom pb-3 mb-4">
                            <i class="bi bi-chat-text"></i> {{ t('inbox') }}
                        </h2>
                        
                        @if (isset($errors) && $errors->any())
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li class="mb-0">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div id="successMsg" class="alert alert-success d-none" role="alert"></div>
                        <div id="errorMsg" class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="container px-0">
                            <div class="row mb-2">
                                <div class="col-md-12 col-lg-12">
                                    <div class="d-flex justify-content-between user-bar-top">
                                        <div class="fs-5">
                                            <p>
                                                <a href="{{ url(urlGen()->getAccountBasePath() . '/messages') }}" class="{{ linkClass() }}">
                                                    <i class="fa-solid fa-inbox"></i>
                                                </a>&nbsp;
                                                @if ($authUserId != data_get($thread, 'p_creator.id'))
                                                    <a href="{{ urlGen()->user(data_get($thread, 'p_creator')) }}" class="{{ linkClass() }}">
                                                        @if (isUserOnline(data_get($thread, 'p_creator')))
                                                            <i class="fa-solid fa-circle text-success"></i>&nbsp;
                                                        @endif
                                                        <span>
                                                            {{ data_get($thread, 'p_creator.name') }}
                                                        </span>
                                                    </a>
                                                @endif
                                                <span>{{ t('Contact request about') }}</span>
                                                <a href="{{ urlGen()->post(data_get($thread, 'post')) }}" class="{{ linkClass() }}">
                                                    {{ data_get($thread, 'post.title') }}
                                                </a>
                                            </p>
                                        </div>
                                        
                                        <div class="call-xhr-action">
                                            <div class="btn-group btn-group-sm">
                                                @if (data_get($thread, 'p_is_important'))
                                                    <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/' . $threadId . '/actions?type=markAsNotImportant') }}"
                                                       class="btn btn-outline-primary markAsNotImportant"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="{{ t('Mark as not important') }}"
                                                    >
                                                        <i class="fa-solid fa-star"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/' . $threadId . '/actions?type=markAsImportant') }}"
                                                       class="btn btn-outline-primary markAsImportant"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="{{ t('Mark as important') }}"
                                                    >
                                                        <i class="fa-regular fa-star"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/' . $threadId . '/delete') }}"
                                                   class="btn btn-outline-primary"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="{{ t('Delete') }}"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                                @if (data_get($thread, 'p_is_unread'))
                                                    <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/' . $threadId . '/actions?type=markAsRead') }}"
                                                       class="btn btn-outline-primary markAsRead"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="{{ t('Mark as read') }}"
                                                    >
                                                        <i class="fa-solid fa-envelope"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ url(urlGen()->getAccountBasePath() . '/messages/' . $threadId . '/actions?type=markAsUnread') }}"
                                                       class="btn btn-outline-primary markAsRead"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="{{ t('Mark as unread') }}"
                                                    >
                                                        <i class="fa-solid fa-envelope-open"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                @include('front.account.messenger.partials.sidebar')
                                
                                <div class="col-md-9 col-lg-10">
                                    <div class="p-0 m-0 message-chat">
                                        <div class="container mx-0 border rounded bg-body pb-3 mb-3">
                                            <div id="messageChatHistory" class="container mt-3 overflow-y-auto" id="listMessages" style="max-height: 550px;">
                                                <div id="linksMessages" class="text-center">
                                                    {!! $linksRender !!}
                                                </div>
                                                
                                                @include('front.account.messenger.messages.messages')
                                            </div>
                                        </div>
                                        
                                        <div class="container px-0 mx-0 type-message">
                                            @php
                                                $updateUrl = url(urlGen()->getAccountBasePath() . '/messages/' . $threadId);
                                            @endphp
                                            <form id="chatForm" role="form" method="POST" action="{{ $updateUrl }}" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                @honeypot
                                                <div class="hstack gap-3 type-form">
                                                    <textarea id="body" name="body"
                                                              maxlength="500"
                                                              rows="5"
                                                              class="form-control me-auto input-write"
                                                              placeholder="{{ t('Type a message') }}"
                                                              style="height: 60px;"
                                                    ></textarea>
                                                    <div class="p-0 m-0 text-nowrap d-flex align-items-center button-wrap">
                                                        <input id="addFile" name="file_path" type="file">
                                                    </div>
                                                    <div class="vr"></div>
                                                    <button id="sendChat" class="btn btn-primary" type="submit">
                                                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

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

@section('after_styles')
    @parent
    <link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput.min.css') }}" rel="stylesheet">
    @if (config('lang.direction') == 'rtl')
        <link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput-rtl.min.css') }}" rel="stylesheet">
    @endif
    @if (str_starts_with($fiTheme, 'explorer'))
        <link href="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.min.css') }}" rel="stylesheet">
    @endif
    <style>
        .file-input {
            display: inline-block;
        }
    </style>
@endsection

@section('after_scripts')
    @parent

    <script>
        var loadingImage = '{{ url('images/spinners/fading-line.gif') }}';
        var loadingErrorMessage = '{{ t('Threads could not be loaded') }}';
        var actionErrorMessage = '{{ t('This action could not be done') }}';
        var title = {
            'seen': '{{ t('Mark as read') }}',
            'notSeen': '{{ t('Mark as unread') }}',
            'important': '{{ t('Mark as important') }}',
            'notImportant': '{{ t('Mark as not important') }}',
        };
    </script>
    <script src="{{ url('assets/js/app/messenger.js') }}" type="text/javascript"></script>
    <script src="{{ url('assets/js/app/messenger-chat.js') }}" type="text/javascript"></script>
    
    <script src="{{ url('assets/plugins/bootstrap-fileinput/js/plugins/sortable.min.js') }}" type="text/javascript"></script>
    <script src="{{ url('assets/plugins/bootstrap-fileinput/js/fileinput.min.js') }}" type="text/javascript"></script>
    <script src="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.js') }}" type="text/javascript"></script>
    <script src="{{ url('common/js/fileinput/locales/' . config('app.locale') . '.js') }}" type="text/javascript"></script>
    
    <script>
        let options = {};
        options.theme = '{{ $fiTheme }}';
        options.language = '{{ config('app.locale') }}';
        options.rtl = {{ (config('lang.direction') == 'rtl') ? 'true' : 'false' }};
        options.allowedFileExtensions = {!! $allowedFileFormatsJson !!};
        options.minFileSize = {{ (int)config('settings.upload.min_file_size', 0) }};
        options.maxFileSize = {{ (int)config('settings.upload.max_file_size', 1000) }};
        options.browseClass = 'btn btn-primary';
        options.browseIcon = '<i class="fa-solid fa-paperclip" aria-hidden="true"></i>';
        options.layoutTemplates = {
            main1: '{browse}',
            main2: '{browse}',
            btnBrowse: '<div tabindex="500" class="{css}"{status}>{icon}</div>',
        };
        
        onDocumentReady((event) => {
            {{-- fileinput (file_path) --}}
            $('#addFile').fileinput(options);
        });
    </script>
@endsection
