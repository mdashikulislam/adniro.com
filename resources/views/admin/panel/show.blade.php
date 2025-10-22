@extends('admin.layouts.master')

@php
	use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
	use App\Models\Page;
	
	/** @var Panel $xPanel */
	$xPanel ??= null;
	
	/** @var Page $model (for example) */
	$model = $xPanel->model;
	
	$showUri = $xPanel->getUrl($entry->getKey());
@endphp

@section('header')
	<div class="row page-titles">
		<div class="col-md-5 col-12 align-self-center">
			<h2 class="mb-0 h3">
				<span class="text-capitalize">{{ trans('admin.preview') }}</span>
				<small>{!! $xPanel->entityName !!}</small>
			</h2>
		</div>
		<div class="col-md-7 col-12 align-self-center d-none d-md-flex justify-content-end">
			<ol class="breadcrumb mb-0 p-0 bg-transparent">
				<li class="breadcrumb-item">
					<a href="{{ urlGen()->adminUrl() }}">{{ trans('admin.dashboard') }}</a>
				</li>
				<li class="breadcrumb-item">
					<a href="{{ $xPanel->getUrl() }}" class="text-capitalize">
						{!! $xPanel->entityNamePlural !!}
					</a>
				</li>
				<li class="breadcrumb-item active d-flex align-items-center">
					{{ trans('admin.preview') }}
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
			<div class="row">
				<div class="col-lg-6">
					@if ($xPanel->hasAccess('list'))
						<a href="{{ $xPanel->getUrl() }}" class="btn btn-primary shadow mb-3">
							<i class="fa-solid fa-angles-left"></i> {{ trans('admin.back_to_all') }}
							<span class="text-lowercase">{!! $xPanel->entityNamePlural !!}</span>
						</a>
					@endif
				</div>
				<div class="col-lg-6 text-end">
					@if ($model->translationEnabled())
						@php
							$availableLocales = $model->getAvailableLocales();
							$appLocale = app()->getLocale();
							$selectedLocale = $availableLocales[request()->input('locale', $appLocale)] ?? $appLocale;
						@endphp
						{{-- Single button --}}
						<div class="btn-group">
							<button type="button"
							        class="btn btn-primary shadow dropdown-toggle"
							        data-bs-toggle="dropdown"
							        aria-haspopup="true"
							        aria-expanded="false"
							>
								{{ trans('admin.Language') }}: {{ $selectedLocale }} &nbsp;<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								@foreach ($availableLocales as $key => $locale)
									@php
										$showUrl = urlBuilder($showUri)->setParameters(['locale' => $key])->toString();
									@endphp
									<a class="dropdown-item ps-3 pe-3 pt-1 pb-1" href="{{ $showUrl }}">
										{{ $locale }}
									</a>
								@endforeach
							</ul>
						</div>
					@endif
				</div>
			</div>
			
			{{-- Default box --}}
			<div class="card rounded-0 border-0 border-top border-primary">
				<div class="card-header border-bottom-0">
					<div class="row">
						<div class="col-lg-6">
							<h3 class="mb-0">{{ trans('admin.preview') . ' ' . $xPanel->entityName }}</h3>
						</div>
						<div class="col-lg-6 text-end">
							<span>
								<a href="javascript: window.print();"><i class="fa fa-print"></i></a>
							</span>
						</div>
					</div>
				</div>
				<div class="card-body">
					<table class="table table-striped table-bordered mb-0">
						<tbody>
						@foreach ($xPanel->columns as $column)
							<tr>
								<td><span class="fw-bold">{{ $column['label'] }}</span></td>
								<td>
									@if (!isset($column['type']))
										@include('admin.panel.columns.text')
									@else
										@if (view()->exists('vendor.admin.panel.columns.' . $column['type']))
											@include('vendor.admin.panel.columns.' . $column['type'])
										@else
											@if (view()->exists('admin.panel.columns.' . $column['type']))
												@include('admin.panel.columns.' . $column['type'])
											@else
												@include('admin.panel.columns.text')
											@endif
										@endif
									@endif
								</td>
							</tr>
						@endforeach
						@if ($xPanel->buttons->where('stack', 'line')->count())
							<tr>
								<td><span class="fw-bold">{{ trans('admin.actions') }}</span></td>
								<td>
									@include('admin.panel.inc.button_stack', ['stack' => 'line'])
								</td>
							</tr>
						@endif
						</tbody>
					</table>
				</div>
			</div>
		
		</div>
	</div>
@endsection

@section('after_styles')
	<link rel="stylesheet" href="{{ asset('assets/admin/crud/css/crud.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/crud/css/show.css') }}">
@endsection

@section('after_scripts')
	<script src="{{ asset('assets/admin/crud/js/crud.js') }}"></script>
	<script src="{{ asset('assets/admin/crud/js/show.js') }}"></script>
@endsection
