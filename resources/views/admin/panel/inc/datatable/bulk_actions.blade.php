<script>
	onDocumentReady((event) => {
		/* Mass Select All */
		$('body').on('change', '#massSelectAll', function () {
			let rows, checked, colIndex;
			
			rows = $('#crudTable').find('tbody tr');
			checked = $(this).prop('checked');
			colIndex = {{ (isset($xPanel->detailsRow) && $xPanel->detailsRow) ? 1 : 0 }};
			
			$.each(rows, function () {
				$($(this).find('td').eq(colIndex)).find('input').prop('checked', checked);
			});
		});
		
		/* Initial bulk actions links registration */
		registerBulkActions();
	});
	
	
	/* ===== FUNCTIONS ===== */
	
	
	/**
	 * Register Mass/Bulk Actions Event
	 */
	function registerBulkActions() {
		$('.bulk-action').click(function (e) {
			e.preventDefault();
			
			const clickedEl = $(this);
			const selectedItems = $('input[name="entryId[]"]:checked');
			
			if (selectedItems.length > 0) {
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
							$.each(selectedItems, function () {
								if (clickedEl.attr('name') === 'deletion') {
									$(this).closest('tr').remove();
								}
							});
							
							return false;
						}
						
						const formEl = $('#bulkActionForm');
						bulkActions(formEl, clickedEl);
						
					} else if (result.dismiss === Swal.DismissReason.cancel) {
						pnotifyAlertClient('info', langLayout.confirm.message.cancel);
					}
				});
			} else {
				let message = "{{ trans('admin.Please select at least one item below') }}";
				jsAlert(message, 'warning');
			}
			
			return false;
		});
	}
	
	/**
	 * Perform Mass/Bulk Actions
	 * @param formEl
	 * @param clickedEl
	 */
	function bulkActions(formEl, clickedEl) {
		/* Retrieve global variables */
		const tableInstance = (typeof table !== 'undefined') ? table : window.tableInstance;
		
		const submitUrl = $(formEl).attr('action');
		
		/* Get all checked checkboxes */
		const selectedItems = $('input[name="entryId[]"]:checked');
		
		/* Form POST data init. */
		const requestInputs = {
			'action': clickedEl.attr('name'), /* Add the clicked button */
			'entryId[]': []
		};
		
		/* Get all checked checkboxes to pass to the jQuery AJAX request */
		selectedItems.each(function() {
			requestInputs['entryId[]'].push($(this).val());
		});
		
		/* Make the AJAX request */
		const ajax = $.ajax({
			url: submitUrl,
			type: 'POST',
			data: requestInputs,
			beforeSend: function () {
				selectedItems.each(function() {
					const thisEl = $(this);
					const thisElTr = thisEl.closest('tr');
					
					/* Hide & disable the element's line's Tooltip(s) */
					const tooltipEl = thisElTr.find('[data-bs-toggle="tooltip"]');
					tooltipEl.tooltip('hide');
					tooltipEl.tooltip('disable');
				});
			}
		});
		ajax.done(function(xhr) {
			if (typeof xhr.success === 'undefined' || typeof xhr.message === 'undefined') {
				return false;
			}
			
			/* Show an alert with the result */
			let messageType = xhr.success ? 'success' : 'error';
			pnotifyAlertClient(messageType, xhr.message);
			
			/* Delete the row from the table */
			$.each(selectedItems, function() {
				if (clickedEl.attr('name') === 'deletion') {
					$(this).parentsUntil('tr').parent().remove();
				}
			});
			
			/* Reload data after row deletion */
			tableInstance.ajax.reload(null, false);
			
			return false;
		});
		ajax.fail(function(xhr) {
			let message = getErrorMessageFromXhr(xhr);
			if (message !== null) {
				pnotifyAlertClient('error', message);
			}
			
			return false;
		});
	}
</script>
