{{-- regular object attribute --}}
<span>
	{{ str($entry->{$column['name']})->stripTags()->limit(80, "[...]") }}
</span>
