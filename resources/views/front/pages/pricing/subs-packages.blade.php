@php
	$packages ??= [];
	$message ??= '';
	$createAccountUrl ??= '';
@endphp
<div id="subsPackages">
	
	<p class="text-center">
		{{ t('subs_packages_hint') }}
	</p>
	
	<div class="row mt-5 justify-content-center">
		@if (is_array($packages) && count($packages) > 0)
			@foreach($packages as $package)
				@php
					$boxClass = (data_get($package, 'recommended') == 1) ? ' border-primary' : '';
					$boxHeaderClass = (data_get($package, 'recommended') == 1) ? ' bg-primary border-primary text-white' : '';
					$boxBtnClass = (data_get($package, 'recommended') == 1) ? ' btn-primary' : ' btn-outline-primary';
				@endphp
				<div class="col-md-4">
					<div class="card mb-4 box-shadow{{ $boxClass }}">
						<div class="card-header text-center{{ $boxHeaderClass }}">
							<h4 class="fw-bold my-1 pb-0">
								{{ data_get($package, 'short_name') }}
							</h4>
						</div>
						<div class="card-body">
							<h2 class="text-center">
								<span class="fw-bold">
									{!! data_get($package, 'price_formatted') !!}
								</span>
								<small class="text-muted">/ {{ data_get($package, 'interval', '--') }}</small>
							</h2>
							<ul class="list-unstyled text-center mt-3 mb-4">
								@if (
									is_array(data_get($package, 'description_array'))
									&& count(data_get($package, 'description_array')) > 0
								)
									@foreach(data_get($package, 'description_array') as $option)
										@php
											$borderClass = !$loop->last ? ' border-bottom mb-2' : '';
										@endphp
										<li class="py-2{{ $borderClass }}">{!! $option !!}</li>
									@endforeach
								@else
									<li class="py-2"> *** </li>
								@endif
							</ul>
							@php
								$pricingUrl = url(urlGen()->getAccountBasePath() . '/subscription');
								$pricingUrl = $pricingUrl . '?packageId=' . data_get($package, 'id');
							@endphp
							<div class="row">
								<div class="col-12 d-grid">
									<a href="{{ $pricingUrl }}" class="btn btn-lg{{ $boxBtnClass }}">
										{{ t('get_started') }}
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			@endforeach
		@else
			<div class="col-md-6 col-sm-12 text-center">
				<div class="card bg-body-secondary">
					<div class="card-body">
						{{ $message ?? null }}
					</div>
				</div>
			</div>
		@endif
	</div>

</div>
