@extends('admin.layouts.master')

@php
    use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
	
    /** @var Panel $xPanel */
    $xPanel ??= null;
@endphp

@section('header')
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <h2 class="mb-0">
                <span class="text-capitalize">{!! $xPanel->entityNamePlural !!}</span>
                <small>{{ trans('admin.add') }} {!! $xPanel->entityName !!}</small>
            </h2>
        </div>
        <div class="col-md-7 col-12 align-self-center d-none d-md-flex justify-content-end">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item">
                    <a href="{{ urlGen()->adminUrl() }}">
                        {{ trans('admin.dashboard') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ $xPanel->getUrl() }}" class="text-capitalize">
                        {!! $xPanel->entityNamePlural !!}
                    </a>
                </li>
                <li class="breadcrumb-item active d-flex align-items-center">
                    {{ trans('admin.add') }}
                </li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="flex-row d-flex justify-content-center">
        @php
            $colMd = config('settings.style.admin_boxed_layout') == '1' ? ' col-md-12' : ' col-md-9';
        @endphp
        <div class="col-sm-12{{ $colMd }}">
            
            {{-- Default box --}}
            @if ($xPanel->hasAccess('list'))
                <a href="{{ $xPanel->getUrl() }}" class="btn btn-primary shadow mb-3">
                    <i class="fa-solid fa-angles-left"></i> {{ trans('admin.back_to_all') }}
                    <span class="text-lowercase">{!! $xPanel->entityNamePlural !!}</span>
                </a>
            @endif
            
            {{-- Show the errors, if any --}}
            @include('admin.panel.inc.grouped_errors')
            
            @if ($xPanel->hasUploadFields('create'))
                {{ html()->form('POST', $xPanel->getUrl())->acceptsFiles()->attribute('novalidate', true)->open() }}
            @else
                {{ html()->form('POST', $xPanel->getUrl())->attribute('novalidate', true)->open() }}
            @endif
            <div class="card rounded-0 border-0 border-top border-primary">
                
                <div class="card-header border-bottom-0">
                    <h3 class="mb-0">{{ trans('admin.add_a_new') }} {!! $xPanel->entityName !!}</h3>
                </div>
                <div class="card-body">
                    {{-- load the view from the application if it exists, otherwise load the one in the package --}}
                    @php
                        $form = 'create';
                    @endphp
                    @if (view()->exists('admin.panel.' . $xPanel->entityName . '.form_content'))
                        @include('admin.panel.' . $xPanel->entityName . '.form_content', [
			                'form'   => $form,
			                'fields' => $xPanel->getFields($form),
			                'action' => 'create',
                        ])
                    @else
                        @include('admin.panel.form_content', [
							'form'   => $form,
							'fields' => $xPanel->getFields($form),
							'action' => 'create',
                        ])
                    @endif
                </div>
                <div class="card-footer px-0 border-top">
                    @include('admin.panel.inc.form_save_buttons')
                </div>
                
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>

@endsection
