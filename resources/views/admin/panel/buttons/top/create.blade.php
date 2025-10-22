@if ($xPanel->hasAccess('create'))
	<a href="{{ $xPanel->getUrl('create') }}" class="btn btn-primary shadow mb-1 ladda-button" data-style="zoom-in">
		<span class="ladda-label">
            <i class="fa-solid fa-plus"></i> {{ trans('admin.add') }} {!! $xPanel->entityName !!}
        </span>
    </a>
@endif
