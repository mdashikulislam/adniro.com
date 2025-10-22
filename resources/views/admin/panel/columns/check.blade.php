{{-- checkbox with loose false/null/0 checking --}}
@php
	$checkValue = data_get($entry, $column['name']);
	$strippedValue = strip_tags($checkValue);
	
	$checkedIcon = data_get($column, 'icons.checked', 'fa fa-check-square-o');
	$uncheckedIcon = data_get($column, 'icons.unchecked', 'fa fa-square-o');
	
	$exportCheckedText = data_get($column, 'labels.checked', 'Yes');
	$exportUncheckedText = data_get($column, 'labels.unchecked', 'No');
	
	$icon = empty($strippedValue) ? $uncheckedIcon : $checkedIcon;
	$text = empty($strippedValue) ? $exportUncheckedText : $exportCheckedText;
@endphp

<span>
    <i class="{{ $icon }}"></i>
</span>

<span class="hidden">{{ $text }}</span>
