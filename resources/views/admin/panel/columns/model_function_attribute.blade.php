{{-- custom return value via attribute --}}
@php
	$value = $entry->{$column['function_name']}()->{$column['attribute']};
	
	$prefix = $column['prefix'] ?? '';
	$limit = $column['limit'] ?? 50;
	$suffix = $column['suffix'] ?? '';
	
	$shortedValue = str($value)->limit($limit, "[...]")->toString();
@endphp
<span>
	@php
		echo $prefix . $shortedValue . $suffix;
	@endphp
</span>
