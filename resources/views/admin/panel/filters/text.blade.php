{{-- Text CRUD filter --}}
@php
	$filter ??= new \stdClass();
	
	$activeClass = request()->query($filter->name) ? ' active' : '';
	$filterSlug = str($filter->name)->slug();
@endphp
<li class="nav-item dropdown{{ $activeClass }}"
    data-filter-name="{{ $filter->name }}"
    data-filter-type="{{ $filter->type }}"
>
	<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
		{{ $filter->label }} <span class="caret"></span>
	</a>
	<div class="dropdown-menu pt-0 pb-0">
		<div class="input-group backpack-filter mb-0">
			<div class="input-group">
				<input class="form-control float-end"
					   id="text-filter-{{ $filterSlug }}"
					   type="text"
					   @if ($filter->currentValue)
					   value="{{ $filter->currentValue }}"
					   @endif
				>
				<span class="input-group-text">
					<a class="text-filter-{{ $filterSlug }}-clear-button" href=""><i class="fa-solid fa-xmark"></i></a>
				</span>
			</div>
		</div>
	</div>
</li>

{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
	{{-- include select2 js--}}
	<script>
		onDocumentReady((event) => {
			$('#text-filter-{{ $filterSlug }}').on('change', function(e) {
				
				const parameter = '{{ $filter->name }}';
				const value = $(this).val();
				
				// behaviour for ajax table
				const ajaxTable = $('#crudTable').DataTable();
				const currentUrl = ajaxTable.ajax.url();
				let newUrl = addOrUpdateUriParameter(currentUrl, parameter, value);
				
				// replace the datatables ajax url with newUrl and reload it
				newUrl = normalizeAmpersand(newUrl.toString());
				ajaxTable.ajax.url(newUrl).load();
				
				// mark this filter as active in the navbar-filters
				if (URI(newUrl).hasQuery('{{ $filter->name }}', true)) {
					$('li[data-filter-name={{ $filter->name }}]').removeClass('active').addClass('active');
				} else {
					$('li[data-filter-name={{ $filter->name }}]').trigger('filter:clear');
				}
			});
			
			$('li[data-filter-name={{ $filterSlug }}]').on('filter:clear', function(e) {
				$('li[data-filter-name={{ $filter->name }}]').removeClass('active');
				$('#text-filter-{{ $filterSlug }}').val('');
			});
			
			// datepicker clear button
			$(".text-filter-{{ $filterSlug }}-clear-button").click(function(e) {
				e.preventDefault();
				
				$('li[data-filter-name={{ $filterSlug }}]').trigger('filter:clear');
				$('#text-filter-{{ $filterSlug }}').val('');
				$('#text-filter-{{ $filterSlug }}').trigger('change');
			})
		});
	</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
