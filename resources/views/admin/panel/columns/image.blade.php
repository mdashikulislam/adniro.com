@php
	$value = $entry->{$column['name']};
	
	$prefix = $column['prefix'] ?? '';
	$width = $column['width'] ?? 'auto';
	$height = $column['height'] ?? '25px';
@endphp
<span>
	@if (!empty($value))
		<a href="{{ asset($prefix . $value) }}" target="_blank">
			<img src="{{ asset($prefix . $value) }}" alt="" class="rounded" style="max-height: {{ $height }}; width: {{ $width }};"/>
		</a>
	@else
		-
	@endif
</span>
