@php
    $apiResult = $apiResult ?? [];
	
	$paginator = (array)data_get($apiResult, 'links');
	
	$firstLink = data_get($paginator, 'first');
	$lastLink = data_get($paginator, 'last');
	$prevLink = data_get($paginator, 'prev');
	$nextLink = data_get($paginator, 'next');
	
	$default = 0;
	
	// Meta Data
	$from = (int)(data_get($apiResult, 'meta.from', $default) ?? $default);
	$to = (int)(data_get($apiResult, 'meta.to', $default) ?? $default);
	$total = (int)(data_get($apiResult, 'meta.total', $default) ?? $default);
	$perPage = (int)(data_get($apiResult, 'meta.per_page', $default) ?? $default);
	$currentPage = (int)(data_get($apiResult, 'meta.current_page', $default) ?? $default);
	$lastPage = (int)(data_get($apiResult, 'meta.last_page', $default) ?? $default);
	
	// Has Pages (Is pageable)
	$hasPages = ($total > $perPage && $total > 0 && (!empty($prevLink) || !empty($nextLink)));
	$onFirstPage = (empty($prevLink) || $to <= $perPage);
	$hasMorePages = !empty($nextLink);
	
	// Links
	$elements = data_get($apiResult, 'meta.links');
	$elements = is_array($elements) ? $elements : [];
	
	// Formatting
	$fromFormatted = '<span class="fw-bold">' . $from . '</span>';
    $toFormatted = '<span class="fw-bold">' . $to . '</span>';
    $totalFormatted = '<span class="fw-bold">' . $total . '</span>';
@endphp
@if ($hasPages)
    <nav class="w-100 mt-3">
        <div class="d-flex justify-content-center flex-fill d-md-none">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($onFirstPage)
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link" aria-hidden="true">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $prevLink }}" rel="prev">
                            @lang('pagination.previous')
                        </a>
                    </li>
                @endif
                
                {{-- Next Page Link --}}
                @if ($hasMorePages)
                    <li class="page-item">
                        <a class="page-link" href="{{ $nextLink }}" rel="next">
                            @lang('pagination.next')
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link" aria-hidden="true">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>
        
        <div class="d-none d-md-flex justify-content-md-center">
            <div class="row">
                <div class="col-12 mb-3">
                    <p class="text-muted mb-0 text-center">
                        {!! t('pagination_meta', ['from' => $fromFormatted, 'to' => $toFormatted, 'total' => $totalFormatted]) !!}
                    </p>
                </div>
                
                <div class="col-12 d-flex justify-content-center">
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($onFirstPage)
                            <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                                <span class="page-link" aria-hidden="true">&lsaquo;</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $prevLink }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                            </li>
                        @endif
                        
                        {{-- Pagination Elements --}}
                        @if (!empty($elements))
                            @foreach ($elements as $element)
                                @continue($loop->first || $loop->last)
                                
                                {{-- "Three Dots" Separator --}}
                                @if (!data_get($element, 'url'))
                                    <li class="page-item disabled" aria-disabled="true">
                                        <span class="page-link">{{ data_get($element, 'label') }}</span>
                                    </li>
                                @else
                                    {{-- Array Of Links --}}
                                    @if ((int)data_get($element, 'label') == $currentPage)
                                        <li class="page-item active" aria-current="page">
                                            <span class="page-link">{{ data_get($element, 'label') }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ data_get($element, 'url') }}">
                                                {{ data_get($element, 'label') }}
                                            </a>
                                        </li>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                        
                        {{-- Next Page Link --}}
                        @if ($hasMorePages)
                            <li class="page-item">
                                <a class="page-link" href="{{ $nextLink }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                            </li>
                        @else
                            <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                                <span class="page-link" aria-hidden="true">&rsaquo;</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </nav>
@else
    @if ($total > 0)
        <div class="text-secondary text-center mt-3">
            {!! t('pagination_meta', ['from' => $fromFormatted, 'to' => $toFormatted, 'total' => $totalFormatted]) !!}
        </div>
    @endif
@endif
