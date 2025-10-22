@php
	$headerMenu ??= [];
	$headerMenuData = (array)data_get($headerMenu, 'data');
	$totalHeaderMenus = (int)data_get($headerMenu, 'meta.total', 0);
	
	$savedType = null;
	$openOnHover = ''; // ' open-on-hover'
@endphp
@foreach($headerMenuData as $menu)
	@php
		$labelHtml = data_get($menu, 'label_html');
		$shouldDisplay = data_get($menu, 'should_display');
		$children = data_get($menu, 'children');
		
		$canBeDisplayed = ($shouldDisplay && !empty($labelHtml));
		
		$type = data_get($menu, 'type');
		$ms = ($savedType == 'button' && $type == 'button') ? ' ms-1' : '';
	@endphp
	@continue(!$canBeDisplayed)
	@if (!empty($children))
		<li class="nav-item{{ $ms }} dropdown{{ $openOnHover }}">
			{!! $labelHtml !!}
			<ul class="dropdown-menu shadow-sm">
				@foreach($children as $subMenu)
					@php
						$subLabelHtml = data_get($subMenu, 'label_html');
						$subChildren = data_get($subMenu, 'children');
						$subShouldDisplay = data_get($subMenu, 'should_display');
						$subCanBeDisplayed = ($subShouldDisplay && !empty($subLabelHtml));
					@endphp
					@continue(!$subCanBeDisplayed)
					<li>
						{!! $subLabelHtml !!}
					</li>
				@endforeach
			</ul>
		</li>
	@else
		<li class="nav-item{{ $ms }}">
			{!! $labelHtml !!}
		</li>
	@endif
	@php
		$savedType = $type;
	@endphp
@endforeach
