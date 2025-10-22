{{-- Category --}}
@if (!empty($cats))
	@php
		// dd($cats);
		$countPostsPerCat ??= [];
		$linkClass = linkClass('body-emphasis');
	@endphp
	<div id="catsList">
		<div class="container p-0 vstack gap-2">
			<h5 class="border-bottom pb-2 d-flex justify-content-between">
				<span class="fw-bold">{{ t('all_categories') }}</span> {!! $clearFilterBtn ?? '' !!}
			</h5>
			<ul class="mb-0 list-unstyled">
				@foreach ($cats as $iCat)
					<li class="py-1">
						@if (isset($cat) && data_get($iCat, 'id') == data_get($cat, 'id'))
							<span class="fw-bold">
								@if (in_array(config('settings.listings_list.show_category_icon'), [4, 5, 6, 8]))
									<i class="{{ data_get($iCat, 'icon_class') ?? 'bi bi-folder-fill' }}"></i>
								@endif
								{{ data_get($iCat, 'name') }}
								@if (config('settings.listings_list.count_categories_listings'))
									&nbsp;<span class="fw-normal">({{ $countPostsPerCat[data_get($iCat, 'id')]['total'] ?? 0 }})</span>
								@endif
							</span>
						@else
							<a href="{{ urlGen()->category($iCat, null, $city ?? null) }}"
							   class="{{ $linkClass }}"
							   title="{{ data_get($iCat, 'name') }}"
							>
								<span>
									@if (in_array(config('settings.listings_list.show_category_icon'), [4, 5, 6, 8]))
										<i class="{{ data_get($iCat, 'icon_class') ?? 'bi bi-folder-fill' }}"></i>
									@endif
									{{ data_get($iCat, 'name') }}
								</span>
								@if (config('settings.listings_list.count_categories_listings'))
									&nbsp;<span class="fw-normal">({{ $countPostsPerCat[data_get($iCat, 'id')]['total'] ?? 0 }})</span>
								@endif
							</a>
						@endif
					</li>
				@endforeach
			</ul>
		</div>
	</div>
@endif
