@php
	$pageBreakpoint ??= [];
	$leftColSize = data_get($pageBreakpoint, 'leftColSize') ?? 'col-md-3';
	$showOnLargeScreen = data_get($pageBreakpoint, 'showOnLargeScreen') ?? ' d-none d-md-block';
	$breakpointSize = (int)(data_get($pageBreakpoint, 'size') ?? 768);
	
	$isPriceFilterCanBeDisplayed = (!empty($cat) && data_get($cat, 'type') != 'not-salable');
@endphp
<div class="{{ $leftColSize }} pb-4" id="leftSidebar">
	{{-- Sidebar (for Large Screens) | d-none d-md-block --}}
	<div class="w-100{{ $showOnLargeScreen }}" id="largeScreenSidebar">
		<aside>
			
			{{-- The #movableSidebarContent element will be moved here based on the client window size --}}
			<div class="card" id="movableSidebarContent">
				<div class="card-body vstack gap-4 text-wrap">
					@include('front.search.partials.sidebar.fields')
					@include('front.search.partials.sidebar.categories')
					@include('front.search.partials.sidebar.cities')
					@if (!config('settings.listings_list.hide_date'))
						@include('front.search.partials.sidebar.date')
					@endif
					@include('front.search.partials.sidebar.price')
				</div>
			</div>
			
		</aside>
	</div>
	
	{{-- Offcanvas (for Mobile Screen) | d-block d-sm-block d-md-none --}}
	<div class="offcanvas offcanvas-start px-0" tabindex="-1" id="smallScreenSidebar" aria-labelledby="smallScreenSidebarLabel">
		<div class="offcanvas-header bg-body-secondary">
			<h5 class="offcanvas-title fw-bold" id="smallScreenSidebarLabel">
				{{ t('Filters') }}
			</h5>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body vh-200 overflow-y-auto">
			
			{{-- The #movableSidebarContent element will be moved here based on the client window size --}}
			
		</div>
	</div>
</div>

@section('after_scripts')
    @parent
    <script>
        var baseUrl = '{{ request()->url() }}';
        
        onDocumentReady((event) => {
			const breakpointSize = {{ $breakpointSize }};
			
	        /* Run on initial load */
	        moveSidebarContentBasedOnScreenSize(breakpointSize);
	        
	        /* Run on window resize */
	        window.addEventListener('resize', () => {
		        moveSidebarContentBasedOnScreenSize(breakpointSize);
	        });
        });
        
        /**
         * Move the sidebar content based on screen size
         * Bootstrap's breakpoints: xxl:1400, xl:1200, lg:992, md:768, sm:576
         *
         * @param breakpointSize
         */
        function moveSidebarContentBasedOnScreenSize(breakpointSize) {
	        const largeScreenDiv = document.querySelector('#largeScreenSidebar aside');
	        const smallScreenDiv = document.querySelector('#smallScreenSidebar .offcanvas-body');
	        const movableContent = document.getElementById('movableSidebarContent');
			
			if (typeof breakpointSize === 'undefined') {
				breakpointSize = 768;
			}
	  
			const isLargeScreen = (window.innerWidth >= breakpointSize);
	        /* const isLargeScreen2 = window.matchMedia(`(min-width: ${breakpointSize}px)`).matches; */
			
	        if (isLargeScreen) {
		        /* Move to large screen div if not already there */
		        if (!largeScreenDiv.contains(movableContent)) {
			        largeScreenDiv.appendChild(movableContent);
		        }
	        } else {
		        /* Move to small screen div if not already there */
		        if (!smallScreenDiv.contains(movableContent)) {
			        smallScreenDiv.appendChild(movableContent);
		        }
	        }
        }
    </script>
    
    {{-- sidebar/date.blade.php --}}
    <script>
	    onDocumentReady((event) => {
		    const postedDateEls = document.querySelectorAll('input[type=radio][name=postedDate]');
		    if (postedDateEls.length > 0) {
			    postedDateEls.forEach((element) => {
				    element.addEventListener('click', (e) => {
					    const queryStringEl = document.querySelector('input[type=hidden][name=postedQueryString]');
					    
					    if (queryStringEl) {
						    let queryString = queryStringEl.value;
						    queryString += (queryString !== '') ? '&' : '';
						    queryString = queryString + 'postedDate=' + e.target.value;
						    
						    let searchUrl = baseUrl + '?' + queryString;
						    redirect(searchUrl);
					    }
				    });
			    });
		    }
	    });
    </script>
@endsection
