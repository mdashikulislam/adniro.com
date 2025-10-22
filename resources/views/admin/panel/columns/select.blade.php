{{-- single relationships (1-1, 1-n) --}}
<span>
	@php
		if ($entry->{$column['entity']}()->getResults()) {
	    	echo $entry->{$column['entity']}()->getResults()->{$column['attribute']};
	    }
	@endphp
</span>
