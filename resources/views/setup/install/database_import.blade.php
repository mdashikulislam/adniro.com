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
@section('title', trans('messages.database_import_title'))

@php
	$databaseName = $databaseInfo['database'] ?? null;
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
    $formActionUrl ??= request()->fullUrl();
    $nextStepUrl ??= url('/');
    $nextStepLabel ??= trans('messages.next');
@endphp
@section('content')
	<form name="databaseImportForm" action="{{ $formActionUrl }}" method="POST" novalidate>
		@csrf
		
		<div class="row" style="min-height: 160px;">
			<div class="mb-4 col-md-12">
				<h5 class="mb-0 fs-5 border-bottom pb-3">
					<i class="bi bi-database"></i> {{ trans('messages.database_import_title') }}
				</h5>
			</div>
			
			{{-- overwrite_tables --}}
			@include('helpers.forms.fields.checkbox', [
				'label'     => trans('messages.database_overwrite_tables'),
				'name'      => 'overwrite_tables',
				'switch'    => true,
				'required'  => false,
				'value'     => data_get($databaseInfo, 'overwrite_tables'),
				'hint'      => trans('messages.database_overwrite_tables_hint'),
				'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- alert --}}
			<div class="mb-4 col-md-12 mt-4">
				<div class="alert alert-info">
					{!! trans('messages.database_import_hint', [
						'btnLabel' => trans('messages.database_import_btn_label'),
						'database' => $databaseName
					]) !!}
				</div>
			</div>
			
			{{-- button --}}
			<div class="col-md-12 text-end border-top pt-3 mt-3">
				@if (!empty($previousStepUrl))
					<a href="{{ $previousStepUrl }}" class="btn btn-secondary">
						<i class="fa-solid fa-chevron-left"></i> {!! $previousStepLabel !!}
					</a>
				@endif
				<button type="submit" class="btn btn-primary">
					{!! $nextStepLabel !!} <i class="bi bi-gear"></i>
				</button>
			</div>
		</div>
	</form>
@endsection

@section('after_scripts')
	@parent
	<script>
		onDocumentReady((event) => {
			let overwriteTablesEl = document.querySelector('input[type=checkbox][name="overwrite_tables"]');
			if (!overwriteTablesEl) return;
			
			let overwriteTablesParentEl = overwriteTablesEl.closest('div.form-check');
			if (overwriteTablesParentEl) {
				overwriteTablesParentEl.addEventListener('click', e => toggleOverwriteTablesEl(e.target));
			}
		});
		
		function toggleOverwriteTablesEl(el) {
			if (!el) return;
			
			/* Avoid to apply checkbox checking to the native feature */
			const nativeCheckboxTags = (
				(el.tagName.toLowerCase() === 'label') ||
				(el.tagName.toLowerCase() === 'input' && el.type === 'checkbox')
			);
			if (nativeCheckboxTags) return;
			
			/* If the current element is still a sub-element of searched element, then try to find the searched element */
			const isTheSameEl = (el.tagName.toLowerCase() === 'div' && el.classList.contains('form-check'));
			if (!isTheSameEl) {
				el = el.closest('div.form-check');
			}
			
			const checkboxEl = el.querySelector('input[type=checkbox]');
			if (checkboxEl) {
				if (checkboxEl.tagName.toLowerCase() === 'input' && checkboxEl.type === 'checkbox') {
					checkboxEl.checked = !checkboxEl.checked;
					checkboxEl.dispatchEvent(new Event('change'));
				}
			}
		}
	</script>
@endsection
