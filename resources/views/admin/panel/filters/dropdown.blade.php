{{-- Dropdown CRUD filter --}}
@php
	$filter ??= new \stdClass();
	$activeClass = request()->query($filter->name) ? ' active' : '';
@endphp
<li class="nav-item dropdown{{ $activeClass }}"
    data-filter-name="{{ $filter->name }}"
    data-filter-type="{{ $filter->type }}"
>
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
		{{ $filter->label }} <span class="caret"></span>
	</a>
    <ul class="dropdown-menu">
		<a class="dropdown-item" data-parameter="{{ $filter->name }}" data-key="" href="">-</a>
		<div role="separator" class="dropdown-divider"></div>
		@if (is_array($filter->values) && count($filter->values))
			@foreach($filter->values as $key => $value)
				@if ($key == 'dropdown-separator')
					<div role="separator" class="dropdown-divider"></div>
				@else
					@php
						$activeDdiClass = ($filter->isActive() && $filter->currentValue == $key) ? ' active' : '';
					@endphp
					<li class="dropdown-item{{ $activeDdiClass }}">
						<a data-parameter="{{ $filter->name }}" href="" data-key="{{ $key }}">{{ $value }}</a>
					</li>
				@endif
			@endforeach
		@endif
    </ul>
  </li>


{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS  --}}
{{-- push things in the after_styles section --}}

@push('crud_list_styles')
	<style>
		.navbar-filters .dropdown-menu {
			max-height: 320px;
			overflow-y: auto;
		}
	</style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
    <script>
	    onDocumentReady((event) => {
			$("li.dropdown[data-filter-name={{ $filter->name }}] .dropdown-menu li a").click(function(e) {
				e.preventDefault();
				
				const value = $(this).data('key');
				const parameter = $(this).data('parameter');
				
				let currentUrl, newUrl;
				@if (!$xPanel->ajaxTable())
					// behaviour for normal table
					currentUrl = normalizeAmpersand('{{ request()->fullUrl() }}');
					newUrl = addOrUpdateUriParameter(currentUrl, parameter, value);
					
					// refresh the page to the newUrl
					newUrl = normalizeAmpersand(newUrl.toString());
			    	window.location.href = newUrl;
			    @else
			    	// behaviour for ajax table
					const ajaxTable = $("#crudTable").DataTable();
					currentUrl = ajaxTable.ajax.url();
					newUrl = addOrUpdateUriParameter(currentUrl, parameter, value);

					// replace the datatables ajax url with newUrl and reload it
					newUrl = normalizeAmpersand(newUrl.toString());
					ajaxTable.ajax.url(newUrl).load();

					// mark this filter as active in the navbar-filters
					// mark dropdown items active accordingly
					if (URI(newUrl).hasQuery('{{ $filter->name }}', true)) {
						$("li[data-filter-name={{ $filter->name }}]").removeClass('active').addClass('active');
						$("li[data-filter-name={{ $filter->name }}] .dropdown-menu li").removeClass('active');
						$(this).parent().addClass('active');
					}
					else
					{
						$("li[data-filter-name={{ $filter->name }}]").trigger("filter:clear");
					}
			    @endif
			});

			// clear filter event (used here and by the Remove all filters button)
			$("li[data-filter-name={{ $filter->name }}]").on('filter:clear', function(e) {
				// console.log('dropdown filter cleared');
				$("li[data-filter-name={{ $filter->name }}]").removeClass('active');
				$("li[data-filter-name={{ $filter->name }}] .dropdown-menu li").removeClass('active');
			});
		});
	</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
