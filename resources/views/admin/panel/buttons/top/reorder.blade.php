@if ($xPanel->reorder)
	@if ($xPanel->hasAccess('reorder'))
	  <a href="{{ $xPanel->getUrl('reorder') }}" class="btn btn-secondary shadow mb-1 ladda-button" data-style="zoom-in">
		  <span class="ladda-label">
              <i class="fa-solid fa-up-down-left-right" aria-hidden="true"></i> {{ trans('admin.reorder') }} {!! $xPanel->entityNamePlural !!}
          </span>
      </a>
	@endif
@endif
