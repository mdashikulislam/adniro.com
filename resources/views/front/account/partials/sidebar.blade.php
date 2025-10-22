@php
	use App\Helpers\Common\Num;
	use Illuminate\Support\Collection;
	
	$accountMenu ??= collect();
	$accountMenu = ($accountMenu instanceof Collection) ? $accountMenu : collect();
	
	// Links CSS Class
	$linkClass = linkClass('body-emphasis');
@endphp
<aside>
	<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-4 mb-md-0 vstack gap-4">
		@if ($accountMenu->isNotEmpty())
			@foreach($accountMenu as $group => $menu)
				@php
					$collapseId = str($group)->slug();
				@endphp
				<div class="">
					<h5 class="border-0 fw-bold clearfix">
						{{ $group }}&nbsp;
						<a href="#{{ $collapseId }}"
						   data-bs-toggle="collapse"
						   aria-expanded="false"
						   aria-controls="{{ $collapseId }}"
						   class="float-end {{ $linkClass }}"
						>
							<i class="fa-solid fa-angle-down"></i>
						</a>
					</h5>
					@if (!empty($menu))
						<div class="collapse show" id="{{ $collapseId }}">
							<ul class="list-group">
								@foreach($menu as $key => $item)
									@php
										$activeClass = $item['isActive'] ? 'active' : '';
										$activeAttr = $item['isActive'] ? ' aria-current="true"' : '';
										$activeLinkClass = $item['isActive'] ? 'text-white' : 'link-body-emphasis';
									@endphp
									<li class="list-group-item d-flex justify-content-between align-items-center {{ $activeClass }}"{!! $activeAttr !!}>
										<a href="{{ $item['url'] }}" class="{{ $activeLinkClass }} text-decoration-none">
											<i class="{{ $item['icon'] }}"></i> {{ $item['name'] }}
										</a>
										@if (!empty($item['countVar']))
											<span class="badge rounded-pill text-bg-secondary{{ $item['cssClass'] ?? '' }}">
												{{ Num::short($item['countVar']) }}
											</span>
										@endif
									</li>
								@endforeach
							</ul>
						</div>
					@endif
				</div>
			@endforeach
		@endif
	</div>
</aside>
