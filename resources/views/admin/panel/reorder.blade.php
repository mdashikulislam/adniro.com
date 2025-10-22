@extends('admin.layouts.master')

@php
    use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
	
    /** @var Panel $xPanel */
    $xPanel ??= null;
	
    $entries ??= collect();
	$rootEntries ??= collect();
@endphp

@section('header')
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <h2 class="mb-0">
                <span class="text-capitalize">{!! $xPanel->entityNamePlural !!}</span>
                <small>{{ trans('admin.reorder') }}</small>
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
                    {{ trans('admin.reorder') }}
                </li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="flex-row d-flex justify-content-center">
        @php
            $colMd = config('settings.style.admin_boxed_layout') == '1' ? ' col-md-12' : ' col-md-10';
        @endphp
        <div class="col-sm-12{{ $colMd }}">
            @if ($xPanel->hasAccess('list'))
                <a href="{{ $xPanel->getUrl() }}" class="btn btn-primary shadow mb-3">
                    <i class="fa-solid fa-angles-left"></i> {{ trans('admin.back_to_all') }}
                    <span class="text-lowercase">{!! $xPanel->entityNamePlural !!}</span>
                </a>
            @endif
            
            {{-- Default box --}}
            <div class="card rounded-0 border-0 border-top border-primary">
                <div class="card-header border-bottom-0">
                    <h3 class="mb-0">{!! trans('admin.reorder') . ' ' . $xPanel->entityNamePlural !!}</h3>
                </div>
                <div class="card-body n-sortable">
                    
                    <p>{{ trans('admin.reorder_text') }}</p>
                    
                    @if ($xPanel->hasNestedEntries() && request()->is('*/reorder'))
                        <div class="card text-white bg-info rounded mb-0">
                            <div class="card-body">
                                {{ trans('admin.reorder_rebuilding_nodes') }}
                            </div>
                        </div>
                    @endif
                    
                    <div class="row">
                        @php
                            $colLg = config('settings.style.admin_boxed_layout') == '1' ? ' col-lg-10' : ' col-lg-6';
                        @endphp
                        <div class="col-md-12{{ $colLg }}">
                            
                            <ol class="sortable">
                                @foreach ($rootEntries as $key => $entry)
                                    @php
                                        echo sortableTreeElement($entry, $key, $entries, $xPanel);
                                    @endphp
                                @endforeach
                            </ol>
                            
                            <button id="toArray" class="btn btn-primary shadow ladda-button" data-style="zoom-in">
                                <span class="ladda-label"><i class="fa-regular fa-floppy-disk"></i> {{ trans('admin.save') }}</span>
                            </button>
                            
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
    <link href="{{ asset('assets/plugins/nestedSortable/nestedSortable.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('after_scripts')
    <script src="https://code.jquery.com/ui/1.11.3/jquery-ui.min.js" type="text/javascript"></script>
    <script src="{{ url('assets/plugins/nestedSortable/jquery.mjs.nestedSortable2.js') }}" type="text/javascript"></script>

    <script type="text/javascript">
        onDocumentReady((event) => {
            // initialize the nested sortable plugin
            $('.sortable').nestedSortable({
                forcePlaceholderSize: true,
                handle: 'div',
                helper: 'clone',
                items: 'li',
                opacity: .6,
                placeholder: 'placeholder',
                revert: 250,
                tabSize: 25,
                tolerance: 'pointer',
                toleranceElement: '> div',
                maxLevels: {{ $xPanel->reorderMaxLevel ?? 3 }},

                isTree: true,
                expandOnHover: 700,
                startCollapsed: false
            });

            $('.disclose').on('click', function() {
                $(this).closest('li').toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
            });

            $('#toArray').click(function(e) {
                if (typeof arraied === 'undefined') {
                    let arraied;
                }
                
                // Get the current tree order
                arraied = $('ol.sortable').nestedSortable('toArray', {startDepthCount: 0});

                // log it
                console.log(arraied);

                // send it with POST
                $.ajax({
                    url: '{{ request()->url() }}',
                    type: 'POST',
                    data: { tree: arraied },
                })
                    .done(function() {
                        console.log("success");
                        new PNotify.alert({
                            title: "{{ trans('admin.reorder_success_title') }}",
                            text: "{{ trans('admin.reorder_success_message') }}",
                            type: "success"
                        });
                    })
                    .fail(function() {
                        console.log("error");
                        new PNotify.alert({
                            title: "{{ trans('admin.reorder_error_title') }}",
                            text: "{{ trans('admin.reorder_error_message') }}",
                            type: "error"
                        });
                    })
                    .always(function() {
                        console.log("complete");
                    });

            });

            $.ajaxPrefilter(function(options, originalOptions, xhr) {
                var token = $('meta[name="csrf_token"]').attr('content');

                if (token) {
                    return xhr.setRequestHeader('X-XSRF-TOKEN', token);
                }
            });
        });
    </script>
@endsection
