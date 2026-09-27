@php
	$autocompleteClass ??= '';
	$searchTooltip ??= '';
@endphp
<div class="row search-row search-bar">
	{{-- q --}}
	<div class="col-md-5 col-sm-12 px-0 mb-md-0 mb-2 search-col">
		<div class="hstack gap-0 border px-3 bg-body border-end-md-0 rounded-5 rounded-end-md-0 py-1 search-field">
			<i class="bi bi-search fs-5 search-field-icon"></i>
			<input class="form-control shadow-none rounded-0 border-0" name="q" placeholder="{{ t('what') }}" type="text" value="">
		</div>
	</div>
	
	{{-- location --}}
	<div class="col-md-5 col-sm-12 px-0 mb-md-0 mb-2 search-col">
		<div class="hstack gap-0 border px-3 bg-body border-start-md-0 border-end-md-0 rounded-5 rounded-start-md-0 rounded-end-md-0 py-1 search-field search-field-location">
			<i class="bi bi-geo-alt fs-5 search-field-icon"></i>
			<input class="form-control shadow-none rounded-0 border-0 {{ $autocompleteClass }}"
			       id="locSearch"
			       name="location"
			       placeholder="{{ t('where') }}"
			       type="text"
			       value=""
			       data-old-value=""
			       spellcheck=false
			       autocomplete="off"
			       autocapitalize="off"
			       tabindex="1"{!! $searchTooltip !!}
			>
		</div>
		<input type="hidden" id="lSearch" name="l" value="">
	</div>
	
	{{-- button --}}
	<div class="col-md-2 col-sm-12 px-0 d-grid search-col">
		<button class="btn btn-primary border-primary rounded-5 rounded-start-md-0 search-btn">
			<i class="fa-solid fa-magnifying-glass"></i> <span class="fw-bold d-inline-block d-md-none d-lg-inline-block">{{ t('find') }}</span>
		</button>
	</div>
</div>
