{{-- row number --}}
@php
	$value = $rowNumber;
	
	$prefix = $column['prefix'] ?? '';
	$limit = $column['limit'] ?? 50;
	$suffix = $column['suffix'] ?? '';
	
	$value = strip_tags($value);
	$shortedValue = str($value)->limit($limit, "[...]")->toString();
@endphp
<span>
	{{ $prefix . $shortedValue . $suffix }}
</span>
