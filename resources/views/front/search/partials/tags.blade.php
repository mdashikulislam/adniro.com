@php
	$tags ??= [];
@endphp
@if (config('settings.listings_list.show_listings_tags'))
	@if (!empty($tags))
		<div class="container">
			<div class="card mb-3">
				<div class="card-body">
					<h3 class="card-title">
						<i class="fa-solid fa-tags"></i> {{ t('Tags') }}:
					</h3>
					@foreach($tags as $iTag)
						<span class="d-inline-block border border-inverse bg-body-tertiary rounded-1 py-1 px-2 my-1 me-1">
							<a href="{{ urlGen()->tag($iTag) }}" class="{{ linkClass() }}">
								{{ $iTag }}
							</a>
						</span>
					@endforeach
				</div>
			</div>
		</div>
	@endif
@endif
