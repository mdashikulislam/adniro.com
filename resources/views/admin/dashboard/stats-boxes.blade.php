<div class="row">
	@if (isset($countUnactivatedPosts))
		<div class="col-lg-3 col-6">
			
			<div class="card border-0 bg-orange shadow">
				<div class="card-body">
					<div class="row py-1">
						<div class="col-8 d-flex align-items-center">
							<div>
								<h2 class="fw-light">
									<a href="{{ urlGen()->adminUrl('posts?active=2') }}" class="text-white" style="font-weight: bold;">
									{{ $countUnactivatedPosts }}
									</a>
								</h2>
								<h6 class="text-white">
									<a href="{{ urlGen()->adminUrl('posts?active=2') }}" class="text-white">
									{{ trans('admin.Unactivated listings') }}
									</a>
								</h6>
							</div>
						</div>
						<div class="col-4 d-flex align-items-center justify-content-end">
							<span class="text-white display-6">
								<a href="{{ urlGen()->adminUrl('posts?active=2') }}" class="text-white">
								<i class="fa-regular fa-pen-to-square"></i>
								</a>
							</span>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	@endif
	
	@if (isset($countActivatedPosts))
		<div class="col-lg-3 col-6">
			
			<div class="card border-0 text-bg-success shadow">
				<div class="card-body">
					<div class="row py-1">
						<div class="col-8 d-flex align-items-center">
							<div>
								<h2 class="fw-light">
									<a href="{{ urlGen()->adminUrl('posts?active=1') }}" class="text-white" style="font-weight: bold;">
									{{ $countActivatedPosts }}
									</a>
								</h2>
								<h6 class="text-white">
									<a href="{{ urlGen()->adminUrl('posts?active=1') }}" class="text-white">
									{{ trans('admin.Activated listings') }}
									</a>
								</h6>
							</div>
						</div>
						<div class="col-4 d-flex align-items-center justify-content-end">
							<span class="text-white display-6">
								<a href="{{ urlGen()->adminUrl('posts?active=1') }}" class="text-white">
								<i class="fa-regular fa-circle-check"></i>
								</a>
							</span>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	@endif
	
	@if (isset($countUsers))
		<div class="col-lg-3 col-6">
			
			<div class="card border-0 text-bg-info shadow">
				<div class="card-body">
					<div class="row py-1">
						<div class="col-8 d-flex align-items-center">
							<div>
								<h2 class="fw-light">
									<a href="{{ urlGen()->adminUrl('users') }}" class="text-white" style="font-weight: bold;">
									{{ $countUsers }}
									</a>
								</h2>
								<h6 class="text-white">
									<a href="{{ urlGen()->adminUrl('users') }}" class="text-white">
									{{ mb_ucfirst(trans('admin.users')) }}
									</a>
								</h6>
							</div>
						</div>
						<div class="col-4 d-flex align-items-center justify-content-end">
							<span class="text-white display-6">
								<a href="{{ urlGen()->adminUrl('users') }}" class="text-white">
								<i class="fa-regular fa-circle-user"></i>
								</a>
							</span>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	@endif
	
	@if (isset($countCountries))
		<div class="col-lg-3 col-6">
			
			<div class="card border-0 text-bg-dark text-white shadow">
				<div class="card-body">
					<div class="row py-1">
						<div class="col-8 d-flex align-items-center">
							<div>
								<h2 class="fw-light">
									<a href="{{ urlGen()->adminUrl('countries') }}" class="text-white" style="font-weight: bold;">
									{{ $countCountries }}
									</a>
								</h2>
								<h6 class="text-white">
									<a href="{{ urlGen()->adminUrl('countries') }}" class="text-white">
									{{ trans('admin.Activated countries') }}
									</a>
									<span class="badge bg-body-secondary text-dark"
										  data-bs-placement="bottom"
										  data-bs-toggle="tooltip"
										  type="button"
										  title="{!! trans('admin.launch_your_website_for_several_countries') . ' ' . trans('admin.disabling_or_removing_a_country_info') !!}"
									>
										{{ trans('admin.Help') }} <i class="fa-regular fa-life-ring"></i>
									</span>
								</h6>
							</div>
						</div>
						<div class="col-4 d-flex align-items-center justify-content-end">
							<span class="text-white display-6">
								<a href="{{ urlGen()->adminUrl('countries') }}" class="text-white">
								<i class="fa-solid fa-globe"></i>
								</a>
							</span>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	@endif
</div>

@push('dashboard_styles')
@endpush

@push('dashboard_scripts')
@endpush
