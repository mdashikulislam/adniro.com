{{-- regular object attribute --}}
@php
	$prefix = $column['prefix'] ?? '';
	$decimals = $column['decimals'] ?? 0;
	$suffix = $column['suffix'] ?? '';
	
	$value = $entry->{$column['name']};
@endphp
<span>
	{{ $prefix . number_format($value, $decimals) . $suffix }}
</span>
