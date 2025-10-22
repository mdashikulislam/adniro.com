@if (isset($xPanel->exportButtons) && $xPanel->exportButtons)
	<script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js" type="text/javascript"></script>
	<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js" type="text/javascript"></script>
	<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.bootstrap.min.js" type="text/javascript"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js" type="text/javascript"></script>
	<script src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js" type="text/javascript"></script>
	<script src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js" type="text/javascript"></script>
	<script src="//cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js" type="text/javascript"></script>
	<script src="//cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js" type="text/javascript"></script>
	<script src="//cdn.datatables.net/buttons/1.7.1/js/buttons.colVis.min.js" type="text/javascript"></script>
	
	<script>
		onDocumentReady(() => {
			/* Retrieve global variables */
			const tableInstance = (typeof table !== 'undefined') ? table : window.tableInstance;
			
			const dtButtons = function(buttons) {
				const extended = [];
				for (let i = 0; i < buttons.length; i++) {
					const item = {
						extend: buttons[i],
						exportOptions: {
							columns: [':visible']
						}
					};
					if (buttons[i] === 'pdfHtml5') {
						item.orientation = 'landscape';
					}
					extended.push(item);
				}
				return extended;
			};
			
			/* Reconfigure DataTable with buttons (must run after table init) */
			tableInstance.destroy();
			const newTable = $('#crudTable').DataTable({
				...tableInstance.context[0].oInit,
				dom: '<"ps-0 col-md-6"l>B<"pe-0 col-md-6"f>rt<"col-md-6 ps-0"i><"col-md-6 pe-0"p>',
				buttons: dtButtons([
					'copyHtml5',
					'excelHtml5',
					'csvHtml5',
					'pdfHtml5',
					'print',
					'colvis'
				])
			});
			window.tableInstance = newTable;
			
			/* Style buttons */
			/* Move the datatable buttons in the top-right corner and make them smaller */
			newTable.buttons().each(function(button) {
				if (button.node.className.indexOf('buttons-columnVisibility') === -1) {
					button.node.className += " btn-sm";
				}
			});
			$('.dt-buttons').appendTo($('#datatable_button_stack'));
		});
	</script>
@endif
