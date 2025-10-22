@php
	// Clear Filter Button
	$clearFilterBtn = urlGen()->getDateFilterClearLink($cat ?? null, $city ?? null);
@endphp
{{-- Date --}}
<div class="container p-0 vstack gap-2">
	<h5 class="border-bottom pb-2 d-flex justify-content-between">
		<span class="fw-bold">{{ t('Date Posted') }}</span> {!! $clearFilterBtn !!}
	</h5>
	<ul class="mb-0 list-unstyled ps-1">
		@if (!empty($periodList))
			@foreach($periodList as $key => $value)
				<li class="p-1">
					<input type="radio"
					       name="postedDate"
					       value="{{ $key }}"
					       id="postedDate_{{ $key }}" {{ (request()->query('postedDate')==$key) ? 'checked="checked"' : '' }}
					>
					<label for="postedDate_{{ $key }}" class="fw-normal">{{ $value }}</label>
				</li>
			@endforeach
		@endif
		<input type="hidden"
		       id="postedQueryString"
		       name="postedQueryString"
		       value="{{ \App\Helpers\Common\Arr::query(request()->except(['page', 'postedDate'])) }}"
		>
	</ul>
</div>

@section('after_scripts')
	@parent
	{{-- Check out the JS code at: "../sidebar.blade.php" --}}
@endsection
