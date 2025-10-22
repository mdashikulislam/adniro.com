@if ($xPanel->detailsRow)
	<script>
		onDocumentReady((event) => {
			registerDetailsRowButtonAction();
		});
		
		
		/* ===== FUNCTIONS ===== */
		
		
		if (typeof registerDetailsRowButtonAction != 'function') {
			function registerDetailsRowButtonAction() {
				/* Retrieve global variables */
				const tableInstance = (typeof table !== 'undefined') ? table : window.tableInstance;
				const registerDeleteButtonActionFunction = (typeof registerDeleteButtonAction !== 'undefined')
					? registerDeleteButtonAction
					: window.registerDeleteButtonActionFunction;
				
				/* Add event listener for opening and closing details */
				$('#crudTable tbody').on('click', 'td .details-row-button', function () {
					const tr = $(this).closest('tr');
					const btn = $(this);
					const row = tableInstance.row(tr);
					
					if (row.child.isShown()) {
						
						/* This row is already open - close it */
						$(this).removeClass('fa-minus-square').addClass('fa-plus-square');
						$('div.table_row_slider', row.child()).slideUp(function () {
							row.child.hide();
							tr.removeClass('shown');
						});
						
					} else {
						
						/* Open this row */
						$(this).removeClass('fa-plus-square').addClass('fa-minus-square');
						
						/* Get the details with ajax */
						const ajax = $.ajax({
							url: '{{ request()->url() }}/' + btn.data('entry-id') + '/details',
							type: 'GET',
						});
						ajax.done(function (xhr) {
							row.child("<div class='table_row_slider'>" + xhr + "</div>", 'p-0').show();
							tr.addClass('shown');
							$('div.table_row_slider', row.child()).slideDown();
							registerDeleteButtonAction();
						});
						ajax.fail(function (xhr) {
							row.child("<div class='table_row_slider'>{{ trans('admin.details_row_loading_error') }}</div>").show();
							tr.addClass('shown');
							$('div.table_row_slider', row.child()).slideDown();
						});
						
					}
				});
				
				/* Destroying the DataTable's 'draw.dt' event */
				$(document).off('draw.dt', '#crudTable');
				
				/* Re-attach the DataTable's 'draw.dt' event by merging the details row logic */
				/* Re-register on draw */
				/* Make the delete button work on subsequent result pages */
				$('#crudTable').on('draw.dt', () => {
					registerDeleteButtonActionFunction();
					registerDetailsRowButtonAction();
				}).dataTable();
			}
		}
	</script>
@endif
