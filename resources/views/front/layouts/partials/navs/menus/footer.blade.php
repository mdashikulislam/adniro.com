@php
	$footerMenu ??= [];
	$footerMenuData = (array)data_get($footerMenu, 'data');
	$totalFooterMenus = (int)data_get($footerMenu, 'meta.total', 0);
@endphp
@foreach($footerMenuData as $menu)
	@php
		$labelHtml = data_get($menu, 'label_html');
		$shouldDisplay = data_get($menu, 'should_display');
		$children = data_get($menu, 'children');
		$canBeDisplayed = ($shouldDisplay && !empty($labelHtml));
	@endphp
	@continue(!$canBeDisplayed)
	@if (!empty($children))
		<div class="col">
			{!! $labelHtml !!}
			<ul class="mb-0 list-unstyled">
				@foreach($children as $subMenu)
					@php
						$subLabelHtml = data_get($subMenu, 'label_html');
						$subChildren = data_get($subMenu, 'children');
						$subShouldDisplay = data_get($subMenu, 'should_display');
						$subCanBeDisplayed = ($subShouldDisplay && !empty($subLabelHtml));
					@endphp
					@continue(!$subCanBeDisplayed)
					<li class="lh-lg">
						{!! $subLabelHtml !!}
					</li>
				@endforeach
			</ul>
		</div>
	@else
		<div class="col">
			{!! $labelHtml !!}
		</div>
	@endif
@endforeach
