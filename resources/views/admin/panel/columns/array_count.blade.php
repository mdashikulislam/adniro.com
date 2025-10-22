{{-- enumerate the values in an array  --}}
<span>
	@php
		$array = $entry->{$column['name']};
		
		// The value should be an array weather or not attribute casting is used
		$array = is_array($array) ? $array : json_decode($array, true);
		$array = is_array($array) ? $array : [];
		$strValue = !empty($array) ? count($array) . ' items' : '-';
		
		echo $strValue;
	@endphp
</span>
