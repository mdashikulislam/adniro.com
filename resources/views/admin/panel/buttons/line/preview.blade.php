@if ($xPanel->hasAccess('show'))
	<a href="{{ $xPanel->getUrl($entry->getKey()) }}" class="btn btn-xs btn-secondary">
		<i class="fa-regular fa-eye"></i> {{ trans('admin.preview') }}
	</a>
@endif
