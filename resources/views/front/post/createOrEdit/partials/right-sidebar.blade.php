@php
	$post ??= [];
@endphp
<div class="vstack gap-4">
	
	@if (request()->segment(1) == 'create' || request()->segment(2) == 'create')
		{{-- Create Form --}}
		<div class="vstack gap-3 text-center">
			<i class="fa-regular fa-image fa-4x text-warning"></i>
			<h5 class="mb-0 fs-5 fw-bold">
				{{ t('create_new_listing') }}
			</h5>
			<p>
				{{ t('do_you_have_something_text', ['appName' => config('app.name')]) }}
			</p>
		</div>
	@else
		{{-- Edit Form --}}
		@if (isSingleStepFormEnabled())
			{{-- Single Step Form --}}
			@if (auth()->check())
				@if (auth()->user()->getAuthIdentifier() == data_get($post, 'user_id'))
					<div class="card">
						<div class="card-header fw-bold text-center">
							{{ t('author_actions') }}
						</div>
						<div class="card-body text-center">
							<div class="d-grid">
								<a href="{{ urlGen()->post($post) }}" class="btn btn-outline-primary">
									<i class="fa-regular fa-hand-point-right"></i> {{ t('Return to the listing') }}
								</a>
							</div>
						</div>
					</div>
				@endif
			@endif
			
		@else
			{{-- Multi Steps Form --}}
			@if (auth()->check())
				@if (auth()->user()->getAuthIdentifier() == data_get($post, 'user_id'))
					<div class="card">
						<div class="card-header fw-bold text-center">
							{{ t('author_actions') }}
						</div>
						<div class="card-body text-center">
							<div class="d-grid vstack gap-2">
								<a href="{{ urlGen()->post($post) }}" class="btn btn-outline-primary">
									<i class="fa-regular fa-hand-point-right"></i> {{ t('Return to the listing') }}
								</a>
								<a href="{{ url('posts/' . data_get($post, 'id') . '/photos') }}" class="btn btn-secondary">
									<i class="fa-solid fa-camera"></i> {{ t('Update Photos') }}
								</a>
								@if (isset($countPackages) && isset($countPaymentMethods) && $countPackages > 0 && $countPaymentMethods > 0)
									<a href="{{ url('posts/' . data_get($post, 'id') . '/payment') }}" class="btn btn-success">
										<i class="fa-regular fa-circle-check"></i> {{ t('Make It Premium') }}
									</a>
								@endif
							</div>
						</div>
					</div>
				@endif
			@endif
			
		@endif
	@endif
	
	<div class="card border-primary">
		<div class="card-header fw-bold bg-primary border-primary text-white text-uppercase text-center">
			{{ t('how_to_sell_quickly') }}
		</div>
		<div class="card-body text-start">
			<ul class="list-unstyled vstack gap-2">
				<li><i class="bi bi-check-lg"></i> {{ t('sell_quickly_advice_1') }}</li>
				<li><i class="bi bi-check-lg"></i> {{ t('sell_quickly_advice_2') }}</li>
				<li><i class="bi bi-check-lg"></i> {{ t('sell_quickly_advice_3') }}</li>
				<li><i class="bi bi-check-lg"></i> {{ t('sell_quickly_advice_4') }}</li>
				<li><i class="bi bi-check-lg"></i> {{ t('sell_quickly_advice_5') }}</li>
			</ul>
		</div>
	</div>
	
</div>
