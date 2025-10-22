{{-- DATA TABLES SCRIPT --}}
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/plugins/datatables/extensions/Responsive-2.2.9/js/dataTables.responsive.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/plugins/datatables/extensions/Responsive-2.2.9/js/responsive.bootstrap5.js') }}" type="text/javascript"></script>

{{--
<script src="{{ asset('assets/plugins/datatables/js/pages/datatable/custom-datatable.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/pages/datatable/datatable-basic.init.js') }}"></script>
--}}

<script>
	onDocumentReady((event) => {
		/* DEBUG */
		/* If you don't want your end users to see the alert() message during error. */
		/* $.fn.dataTable.ext.errMode = 'throw'; */
		
		@php
			$defaultPageLength = $xPanel->getDefaultPageLength();
			$defaultPageLength = \Illuminate\Support\Number::clamp($defaultPageLength, min: 1, max: 100);
			$lengthArray = generateNumberRange(min: 10, max: 100, interval: 10,requiredValue: $defaultPageLength);
			$jsLengthArray = collect($lengthArray)->toJson();
		@endphp
		
		const table = $("#crudTable").DataTable({
			"pageLength": {{ $defaultPageLength }},
			"lengthMenu": [{{ $jsLengthArray }}, {{ $jsLengthArray }}],
			/* Disable initial sort */
			"aaSorting": [],
			"language": {
				"emptyTable":     "{{ trans('admin.emptyTable') }}",
				"info":           "{{ trans('admin.info') }}",
				"infoEmpty":      "{{ trans('admin.infoEmpty') }}",
				"infoFiltered":   "{{ trans('admin.infoFiltered') }}",
				"infoPostFix":    "{{ trans('admin.infoPostFix') }}",
				"thousands":      "{{ trans('admin.thousands') }}",
				"lengthMenu":     "{{ trans('admin.lengthMenu') }}",
				"loadingRecords": "{{ trans('admin.loadingRecords') }}",
				"processing":     "{{ trans('admin.processing') }}",
				"search":         "{{ trans('admin.search') }}",
				"zeroRecords":    "{{ trans('admin.zeroRecords') }}",
				"paginate": {
					"first":      "{{ trans('admin.paginate.first') }}",
					"last":       "{{ trans('admin.paginate.last') }}",
					"next":       "{{ trans('admin.paginate.next') }}",
					"previous":   "{{ trans('admin.paginate.previous') }}"
				},
				"aria": {
					"sortAscending":  "{{ trans('admin.aria.sortAscending') }}",
					"sortDescending": "{{ trans('admin.aria.sortDescending') }}"
				}
			},
			@php
				$isResponsive = $xPanel->getResponsiveTable() ? 'true' : 'false';
			@endphp
			responsive: {{ $isResponsive }},
			@if (!$xPanel->getResponsiveTable())
				scrollX: true,
			@endif
			
			@if ($xPanel->ajaxTable)
				@php
					$searchUrl = $xPanel->getUrl('search');
					$searchUrl = urlBuilder($searchUrl)->setParameters(request()->query())->toString();
				@endphp
				"ajax": {
					"url": "{{ $searchUrl }}",
					"type": "POST",
					beforeSend: function () {
						/* Loading (Show) */
						const loadingDataEl = $('#loadingData');
						loadingDataEl.busyLoad('hide');
						loadingDataEl.busyLoad('show', {
							text: "{{ t('loading_wd') }}",
							custom: createCustomSpinnerEl()
						});
					}
				},
				/* "processing": true, */
				"serverSide": true,
			@endif
			
			@if ($xPanel->isBulkActionAllowed())
				/* Mass Select All */
				'columnDefs': [{
					'targets': [0],
					'orderable': false
				}],
			@endif
			
			@if ($xPanel->hideSearchBar)
				searching: false,
			@endif
			
			/* Fire some actions after the data has been retrieved and renders the table */
			/* NOTE: This only fires once though. */
			'initComplete': function(settings, json) {
				/* $('[data-bs-toggle="tooltip"]').tooltip(); */
				/* $('[data-bs-toggle="tooltipHover"]').tooltip(); */
				
				/* Enable the tooltip */
				/* To prevent the tooltip in bootstrap doesn't work after ajax, use selector on exist element like body */
				const bodyEl = $('body');
				bodyEl.tooltip({selector: '[data-bs-toggle="tooltip"]'});
				bodyEl.tooltip({selector: '[data-bs-toggle="tooltipHover"]'});
			},
			
			/* Called before the DataTable redraw the table */
			preDrawCallback : function (settings) {},
			
			/* Called after the DataTable redraw the table */
			drawCallback : function() {
				/* Loading (Hide) */
				const loadingDataEl = $('#loadingData');
				loadingDataEl.busyLoad('hide');
				
				/* Page Info */
				let info = this.api().page.info();
				let textInfo = "{{ trans('admin.info') }}";
				textInfo = textInfo.replace('_START_', (info.recordsTotal > 0) ? (info.start + 1) : 0);
				textInfo = textInfo.replace('_END_', info.end);
				textInfo = textInfo.replace('_TOTAL_', addThousandsSeparator(info.recordsTotal, '{{ trans('admin.thousands') }}'));
				if (info.recordsTotal <= 0) {
					textInfo = '{{ trans('admin.infoEmpty') }}';
				}
				$('#tableInfo').html(textInfo);
			}
		});
		
		// Expose table instance globally for use in other modules
		window.tableInstance = table;
		
		/* Set how DataTables will report detected errors */
		$.fn.dataTable.ext.errMode = function (settings, techNote, message) {
			message = getErrorMessageFromXhr(settings?.jqXHR, message);
			jsAlert(message, 'error', false);
		};
		
		$.ajaxPrefilter(function(options, originalOptions, xhr) {
			let token = $('meta[name="csrf_token"]').attr('content');
			
			if (token) {
				return xhr.setRequestHeader('X-XSRF-TOKEN', token);
			}
		});
		
		/* Initial delete button registration */
		/* Make the delete button work in the first result page */
		registerDeleteButtonAction();
		
		/* Re-register on draw */
		/* Make the delete button work on subsequent result pages */
		$('#crudTable').on('draw.dt', () => registerDeleteButtonAction()).dataTable();
		
		// Expose functions globally for use in other modules
		window.registerDeleteButtonActionFunction = registerDeleteButtonAction;
	});
	
	
	/* ===== FUNCTIONS ===== */
	
	
	/**
	 * Register the delete button action
	 */
	function registerDeleteButtonAction() {
		const deleteBtnEl = $('[data-button-type=delete]');
		
		deleteBtnEl.unbind('click');
		/* CRUD Delete */
		/* Ask for confirmation before deleting an item */
		deleteBtnEl.click(function(e) {
			e.preventDefault();
			
			const jsThis = this;
			
			Swal.fire({
				position: 'top',
				text: langLayout.confirm.message.question,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: langLayout.confirm.button.yes,
				cancelButtonText: langLayout.confirm.button.no
			}).then((result) => {
				if (result.isConfirmed) {
					
					if (isDemoDomain()) {
						/* Delete the row from the table */
						$(jsThis).closest('tr').remove();
						
						return false;
					}
					
					deleteEntry(jsThis);
					
				} else if (result.dismiss === Swal.DismissReason.cancel) {
					pnotifyAlertClient('info', langLayout.confirm.message.cancel);
				}
			});
		});
	}
	
	/**
	 * Delete an entry
	 * @param jsThis
	 */
	function deleteEntry(jsThis) {
		const tableInstance = (typeof table !== 'undefined') ? table : window.tableInstance;
		
		const deleteButtonEl = $(jsThis);
		const deleteButtonUrl = deleteButtonEl.attr('href');
		const deleteButtonTr = deleteButtonEl.closest('tr');
		{{-- $(selector).parentsUntil('tr').parent() <=> $(selector).closest('tr') --}}
		
		/* Make the AJAX request */
		const ajax = $.ajax({
			url: deleteButtonUrl,
			type: 'DELETE',
			beforeSend: function () {
				/* Hide & disable the element's line's Tooltip(s) */
				const tooltipEl = deleteButtonTr.find('[data-bs-toggle="tooltip"]');
				tooltipEl.tooltip('hide');
				tooltipEl.tooltip('disable');
			}
		});
		ajax.done(function(xhr) {
			/* Show an alert with the result */
			pnotifyAlertClient('success', langLayout.confirm.message.success);
			
			/* Delete the row from the table */
			deleteButtonTr.remove();
			
			/* Reload data after row deletion */
			tableInstance.ajax.reload(null, false);
		});
		ajax.fail(async function(xhr) {
			let message = await extractAjaxErrorMessage(xhr);
			if (message !== null) {
				pnotifyAlertClient('error', message);
			}
		});
	}
	
	/**
	 * Add Thousands Separator (for DataTable Info)
	 * @param nStr
	 * @param separator
	 * @returns {*}
	 */
	function addThousandsSeparator(nStr, separator = ',') {
		nStr += '';
		nStr = nStr.replace(separator, '');
		let x = nStr.split('.');
		let x1 = x[0];
		let x2 = x.length > 1 ? '.' + x[1] : '';
		let rgx = /(\d+)(\d{3})/;
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + separator + '$2');
		}
		return x1 + x2;
	}
</script>
