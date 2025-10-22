@extends('admin.layouts.master')

@php
	/** @var \App\Http\Controllers\Web\Admin\Panel\Library\Panel $xPanel */
	$xPanel ??= null;
@endphp

@section('header')
	<div class="row page-titles">
		<div class="col-md-6 col-12 align-self-center">
			<h2 class="mb-0">
				<span class="text-capitalize">{!! $xPanel->entityNamePlural !!}</span>
				<small id="tableInfo">{{ trans('admin.all') }}</small>
			</h2>
		</div>
		<div class="col-md-6 col-12 align-self-center d-none d-md-flex justify-content-end">
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
					{{ trans('admin.list') }}
				</li>
			</ol>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-12">
			
			@if (isTranslatableModel($xPanel->model))
				<div class="card mb-3">
					<div class="card-body">
						<h3 class="card-title">
							<i class="fa-regular fa-circle-question"></i> {{ trans('admin.Help') }}
						</h3>
						<p class="card-text">
							{!! trans('admin.help_translatable_table') !!}
							@if (config('larapen.admin.show_translatable_field_icon'))
								&nbsp;{!! trans('admin.help_translatable_column') !!}
							@endif
						</p>
					</div>
				</div>
			@endif
			
			<div class="card border-0">
				@php
					$topStack = 'top';
					$isTopButtonsExist = ($xPanel->buttons->where('stack', $topStack)->count() > 0);
				@endphp
				@if ($isTopButtonsExist)
					<div class="card-header px-0 border-bottom border-primary">
						@include('admin.panel.inc.button_stack', ['stack' => $topStack])
						<div id="datatable_button_stack" class="float-end text-end"></div>
					</div>
				@endif
				
				{{-- List Filters --}}
				@if ($xPanel->filtersEnabled())
					<div class="card-body">
						@include('admin.panel.inc.filters_navbar')
					</div>
				@endif
				
				<div class="card-body">
					
					<div id="loadingData"></div>
					
					<form id="bulkActionForm" action="{{ $xPanel->getUrl('bulk_actions') }}" method="POST">
						@csrf
						
						<table id="crudTable" class="dataTable table table-bordered table-striped display dt-responsive nowrap w-100">
							<thead>
							<tr>
								@if ($xPanel->detailsRow)
									<th data-orderable="false"></th> {{-- expand/minimize button column --}}
								@endif
	
								{{-- Table columns --}}
								@foreach ($xPanel->columns as $column)
									@php
										$orderableAttr = isset($column['orderable']) ? ' data-orderable=' . var_export($column['orderable'], true) : '';
										$priorityAttr = isset($column['priority']) ? ' data-priority=' . $column['priority'] : '';
										$visibleInModal = (isset($column['visibleInModal']) && $column['visibleInModal'] === false) ? 'false' : 'true';
										$visibleInModalAttr = "data-visible-in-modal={$visibleInModal}";
									@endphp
									@if ($column['type'] == 'checkbox')
										<th {{ $visibleInModalAttr . $orderableAttr . $priorityAttr }}
											class="dt-checkboxes-cell dt-checkboxes-select-all sorting_disabled"
											tabindex="0"
											aria-controls="massSelectAll"
											rowspan="1"
											colspan="1"
											style="width: 14px; text-align: center; padding-right: 10px;"
											data-col="0"
											aria-label=""
										>
											<input type="checkbox" id="massSelectAll" name="massSelectAll">
										</th>
									@else
										<th {{ $visibleInModalAttr . $orderableAttr . $priorityAttr }}>
											{!! $column['label'] !!}
										</th>
									@endif
								@endforeach
	
								@if ($xPanel->buttons->where('stack', 'line')->count())
									<th data-orderable="false">{{ trans('admin.actions') }}</th>
								@endif
							</tr>
							</thead>
	
							<tbody>
							</tbody>
	
							<tfoot>
							<tr>
								@if ($xPanel->detailsRow)
									<th></th> {{-- expand/minimize button column --}}
								@endif
	
								{{-- Table columns --}}
								@foreach ($xPanel->columns as $column)
									<th>{{ $column['label'] }}</th>
								@endforeach
	
								@if ( $xPanel->buttons->where('stack', 'line')->count() )
									<th>{{ trans('admin.actions') }}</th>
								@endif
							</tr>
							</tfoot>
						</table>
						
					</form>

				</div>

				@include('admin.panel.inc.button_stack', ['stack' => 'bottom'])
				
        	</div>
    	</div>
	</div>
@endsection

@section('after_styles')
    {{-- DATA TABLES --}}
	{{--<link href="{{ asset('assets/plugins/datatables/css/jquery.dataTables.css') }}" rel="stylesheet" type="text/css" />--}}
	<link href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('assets/plugins/datatables/extensions/Responsive-2.2.9/css/responsive.bootstrap5.css') }}" rel="stylesheet" type="text/css" />
    
    <link rel="stylesheet" href="{{ asset('assets/admin/crud/css/crud.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/crud/css/form.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/crud/css/list.css') }}">
	
    {{-- CRUD LIST CONTENT - crud_list_styles stack --}}
    @stack('crud_list_styles')
    
    <style>
		@if ($xPanel->isBulkActionAllowed())
			/* tr > td:first-child, */
			table.dataTable > tbody > tr:not(.p-0) > td:first-child {
				width: 30px;
				white-space: nowrap;
				text-align: center;
			}
		@endif
		
		/* Fix the 'Actions' column size */
		/* tr > td:last-child, */
		table.dataTable > tbody > tr:not(.p-0) > td:last-child,
		table:not(.dataTable) > tbody > tr > td:last-child {
			width: 10px;
			white-space: nowrap;
		}
    </style>
@endsection

@section('after_scripts')
	{{-- DataTable Implementation --}}
	@include('admin.panel.inc.datatable')
	@include('admin.panel.inc.datatable.details_row')
	@include('admin.panel.inc.datatable.bulk_actions')
	@include('admin.panel.inc.datatable.export_buttons')
	
	<script src="{{ asset('assets/admin/crud/js/crud.js') }}"></script>
	<script src="{{ asset('assets/admin/crud/js/form.js') }}"></script>
	<script src="{{ asset('assets/admin/crud/js/list.js') }}"></script>

    {{-- CRUD LIST CONTENT - crud_list_scripts stack --}}
    @stack('crud_list_scripts')
@endsection
