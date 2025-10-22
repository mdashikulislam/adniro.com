{{-- custom return value --}}
@php
	$functionName = $column['function_name'];
	$prefix = $column['prefix'] ?? '';
	$limit = $column['limit'] ?? 50;
	$suffix = $column['suffix'] ?? '';
	
	$xPanel ??= null;
	$value = $entry->{$functionName}($xPanel, $column);
@endphp
<span>
	@php
		echo $prefix . $value . $suffix;
	@endphp
</span>
