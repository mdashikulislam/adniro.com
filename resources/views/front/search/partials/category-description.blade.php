@php
	$cat ??= [];
@endphp
@if (!empty(data_get($cat, 'description')))
	@if (!(bool)data_get($cat, 'hide_description'))
		<div class="container mb-3">
			<div class="card border-light text-dark bg-body-tertiary mb-3">
				<div class="card-body">
					{!! data_get($cat, 'description') !!}
				</div>
			</div>
		</div>
	@endif
@endif
