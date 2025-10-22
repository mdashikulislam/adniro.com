{{-- enumerate the values in an array  --}}
<span>
	@php
		$value = $entry->{$column['name']};
		
		// The value should be an array weather or not attribute casting is used
		$value = is_array($value) ? $value : json_decode($value, true);
		$value = is_array($value) ? $value : [];
		$strValue = !empty($value) ? implode(', ', $value) : '-';
		
		echo $strValue;
	@endphp
</span>
