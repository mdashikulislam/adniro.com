@php
	$catDisplayType ??= 'c_bigIcon_list';
	
	$apiResult ??= [];
	$totalCategories = (int)data_get($apiResult, 'meta.total', 0);
	$areCategoriesPageable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	
	$categories ??= [];
	$category ??= null;
	$hasChildren ??= false;
	$selectedId ??= 0; /* The selected category ID */
	
	$selectionUrl = url('browsing/categories/select');
	
	// Links CSS Class
	$linkClass = linkClass();
@endphp
@if (!$hasChildren)
	
	{{-- To append in the form (will replace the category field) --}}
	
	@if (!empty($category))
		@php
			$_catId = data_get($category, 'id');
			$_catName = data_get($category, 'name');
		@endphp
		@if (!empty(data_get($category, 'children')))
			@php
				$_catSelectionUrl = urlBuilder($selectionUrl)->setParameters(['parentId' => $_catId])->toString();
			@endphp
			<a href="#browseCategories"
			   data-bs-toggle="modal"
			   class="modal-cat-link open-selection-url {{ $linkClass }}"
			   data-selection-url="{{ $_catSelectionUrl }}"
			>
				{{ $_catName }}
			</a>
		@else
			@php
				$_catParentId = data_get($category, 'parent.id', 0);
				$_catSelectionUrl = urlBuilder($selectionUrl)->setParameters(['parentId' => $_catParentId])->toString();
			@endphp
			{{ $_catName }}&nbsp;
			[ <a href="#browseCategories"
				 data-bs-toggle="modal"
				 class="modal-cat-link open-selection-url {{ $linkClass }}"
				 data-selection-url="{{ $_catSelectionUrl }}"
			><i class="fa-regular fa-pen-to-square"></i> {{ t('Edit') }}</a> ]
		@endif
	@else
		<a href="#browseCategories"
		   data-bs-toggle="modal"
		   class="modal-cat-link open-selection-url {{ $linkClass }}"
		   data-selection-url="{{ $selectionUrl }}"
		>
			{{ t('select_a_category') }}
		</a>
	@endif
	
@else
	
	{{-- To append in the modal (will replace the modal content) --}}

	@if (!empty($category))
		@php
			$_parentId = data_get($category, 'parent.id', 0);
			$_url = urlBuilder($selectionUrl)->setParameters(['parentId' => $_parentId])->toString();
			$_id = data_get($category, 'id');
			$_name = data_get($category, 'name');
		@endphp
		<p>
			<a href="{!! $_url !!}" class="btn btn-primary btn-sm modal-cat-link" data-ignore-guard="true">
				<i class="fa-solid fa-reply"></i> {{ t('go_to_parent_categories') }}
			</a>&nbsp;
			<strong>{{ $_name }}</strong>
		</p>
	@endif
	
	@if (!empty($categories))
		<div class="container">
			@if ($catDisplayType == 'c_picture_list')
				
				<div id="modalCategoryList" class="row row-cols-lg-6 row-cols-md-4 row-cols-sm-3 row-cols-2 py-1 px-0">
					@foreach($categories as $key => $cat)
						@php
							$_id = data_get($cat, 'id');
							$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = ($_id != $selectedId || $_hasChildren == 1);
							$_type = data_get($cat, 'type');
							$_imageUrl = data_get($cat, 'image_url');
							$_name = data_get($cat, 'name');
							$_url = urlBuilder($selectionUrl)->setParameters(['parentId' => $_id])->toString();
						@endphp
						<div class="col px-0 d-flex justify-content-center align-content-stretch">
							<div class="text-center justify-content-center w-100 border rounded px-3 py-2 m-1">
								@if ($_hasLink)
									<a href="{!! $_url !!}"
									   class="modal-cat-link {{ $linkClass }}"
									   data-parent-id="{{ $_parentId }}"
									   data-id="{{ $_id }}"
									   data-has-children="{{ $_hasChildren }}"
									   data-type="{{ $_type }}"
									>
								@endif
								<img src="{{ $_imageUrl }}" class="lazyload img-fluid" alt="{{ $_name }}">
								<h6 class="mt-2 fw-bold{{ !$_hasLink ? ' text-secondary' : '' }}">
									{{ $_name }}
								</h6>
								@if ($_hasLink)
									</a>
								@endif
							</div>
						</div>
					@endforeach
				</div>
			
			@elseif ($catDisplayType == 'c_bigIcon_list')
			
				<div id="modalCategoryList" class="row row-cols-lg-6 row-cols-md-4 row-cols-sm-3 row-cols-2 py-0 px-0">
					@foreach($categories as $key => $cat)
						@php
							$_id = data_get($cat, 'id');
							$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = ($_id != $selectedId || $_hasChildren == 1);
							$_type = data_get($cat, 'type');
							$_iconClass = data_get($cat, 'icon_class');
							$_name = data_get($cat, 'name');
							$_url = urlBuilder($selectionUrl)->setParameters(['parentId' => $_id])->toString();
						@endphp
						<div class="col px-0 d-flex justify-content-center align-content-stretch">
							<div class="text-center justify-content-center w-100 border rounded px-3 py-2 m-1">
								@if ($_hasLink)
									<a href="{!! $_url !!}"
									   class="modal-cat-link {{ $linkClass }}"
									   data-parent-id="{{ $_parentId }}"
									   data-id="{{ $_id }}"
									   data-has-children="{{ $_hasChildren }}"
									   data-type="{{ $_type }}"
									>
								@endif
									@if (in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]))
										<i class="{{ $_iconClass ?? 'bi bi-folder-fill' }}" style="font-size: 3rem;"></i>
									@endif
									<h6 class="mt-2 fw-bold{{ !$_hasLink ? ' text-secondary' : '' }}">
										{{ $_name }}
									</h6>
								@if ($_hasLink)
									</a>
								@endif
							</div>
						</div>
					@endforeach
				</div>
				
			@else
				
				@php
					$isShowingCategoryIconEnabled = in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]);
					
					$listTypes = ['c_border_list' => 'border-bottom pb-2'];
					$borderBottom = $listTypes[$catDisplayType] ?? '';
					$borderBottom = !empty($borderBottom) ? ' ' . $borderBottom : '';
				@endphp
				<ul id="modalCategoryList" class="row row-cols-lg-3 row-cols-md-2 row-cols-sm-1 row-cols-1 mt-4 list-unstyled">
					@foreach ($categories as $key => $cat)
						@php
							$_catId = data_get($cat, 'id', 0);
							$_catIconClass = $isShowingCategoryIconEnabled ? data_get($cat, 'icon_class', 'fa-solid fa-check') : '';
							$_catIcon = !empty($_catIconClass) ? '<i class="' . $_catIconClass . '"></i> ' : '';
							$_catName = data_get($cat, 'name', '--');
							$_catType = data_get($cat, 'type');
							
							$_hasChildren = !empty(data_get($cat, 'children')) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = ($_catId != $selectedId || $_hasChildren == 1);
							$_hasLinkClass = !$_hasLink ? ' text-secondary fw-bold' : '';
							
							$_url = urlBuilder($selectionUrl)->setParameters(['parentId' => $_catId])->toString();
						@endphp
						<li class="col{{ $_hasLinkClass }} my-2 px-2 d-flex justify-content-center align-content-stretch">
							<div class="w-100{{ $borderBottom }}">
								{!! $_catIcon !!}
								@if ($_hasLink)
									<a href="{!! $_url !!}"
									   class="modal-cat-link {{ $linkClass }}"
									   data-parent-id="{{ $_parentId }}"
									   data-id="{{ $_catId }}"
									   data-has-children="{{ $_hasChildren }}"
									   data-type="{{ $_catType }}"
									>
								@endif
									{{ $_catName }}
								@if ($_hasLink)
									</a>
								@endif
							</div>
						</li>
					@endforeach
				</ul>
			
			@endif
		</div>
		@if ($totalCategories > 0 && $areCategoriesPageable)
			@include('vendor.pagination.api.bootstrap-5')
		@endif
	@else
		{{ $apiMessage ?? t('no_categories_found') }}
	@endif
@endif
