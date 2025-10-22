{{-- select_from_array column --}}
<span>
	@php
		if ($entry->{$column['name']} !== null) {
			if (is_array($entry->{$column['name']})) {
				$arrayOfValues = [];
				
				foreach ($entry->{$column['name']} as $key => $value) {
					$arrayOfValues[] = $column['options'][$value];
				}
				
				if (count($arrayOfValues) > 1) {
					echo implode(', ', $arrayOfValues);
				} else {
					echo $arrayOfValues;
				}
			} else {
				echo $column['options'][$entry->{$column['name']}];
			}
		} else {
			echo "-";
		}
	@endphp
</span>
