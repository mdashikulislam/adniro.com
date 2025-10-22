@php
	$autocompleteClass ??= '';
	$searchTooltip ??= '';
@endphp
<div class="row search-row">
	{{-- q --}}
	<div class="col-md-5 col-sm-12 px-0 mb-md-0 mb-1 search-col">
		{{--hstack gap-0 form-control form-control-lg border-5 border-end-0 border-primary rounded-5 rounded-end-0 py-1--}}
		<div class="hstack gap-0 border px-3 bg-body border-5 border-end-md-0 border-primary rounded-5 rounded-end-md-0 py-1">
			<i class="bi bi-binoculars fs-4 text-secondary"></i>
			<input class="form-control shadow-none rounded-0 border-0" name="q" placeholder="{{ t('what') }}" type="text" value="">
		</div>
	</div>
	
	{{-- location --}}
	<div class="col-md-5 col-sm-12 px-0 mb-md-0 mb-1 search-col">
		{{--hstack gap-0 form-control form-control-lg border-5 border-start-0 border-end-0 border-primary rounded-5 rounded-start-0 rounded-end-0 py-1--}}
		<div class="hstack gap-0 border px-3 bg-body border-5 border-start-md-0 border-end-md-0 border-primary rounded-5 rounded-start-md-0 rounded-end-md-0 py-1">
			<i class="bi bi-geo-alt fs-4 text-secondary"></i>
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
		{{--btn btn-lg btn-primary bg-gradient border-4 border-start-0 border-primary rounded-5 rounded-start-0--}}
		<button class="btn btn-primary bg-gradient border-4 border-start-md-0 border-primary rounded-5 rounded-start-md-0">
			<i class="fa-solid fa-magnifying-glass"></i> <span class="fw-bold d-none d-xl-inline-block">{{ t('find') }}</span>
		</button>
	</div>
</div>
