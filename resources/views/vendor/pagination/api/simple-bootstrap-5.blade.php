@php
    $apiResult = $apiResult ?? [];
	
	$paginator = data_get($apiResult, 'links');
	
	$prevLink = data_get($paginator, 'prev');
	$nextLink = data_get($paginator, 'next');
	
	// Has Pages (Is pageable)
	$hasPages = (!empty($prevLink) || !empty($nextLink));
	$onFirstPage = empty($prevLink);
	$hasMorePages = !empty($nextLink);
@endphp
@if ($hasPages)
    <nav class="mt-3" role="navigation" aria-label="Pagination Navigation">
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($onFirstPage)
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">@lang('pagination.previous')</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $prevLink }}" rel="prev">@lang('pagination.previous')</a>
                </li>
            @endif
            
            {{-- Next Page Link --}}
            @if ($hasMorePages)
                <li class="page-item">
                    <a class="page-link" href="{{ $nextLink }}" rel="next">@lang('pagination.next')</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">@lang('pagination.next')</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
