@php
	$post ??= [];
@endphp
@section('body_class', 'page-listing-form')
<div class="vstack gap-4 listing-form-aside">
	
	@if (request()->segment(1) == 'create' || request()->segment(2) == 'create')
		{{-- Create Form --}}
		<div class="listing-aside-intro">
			<span class="listing-aside-intro-glow" aria-hidden="true"></span>
			<span class="listing-aside-intro-icon"><i class="bi bi-megaphone-fill"></i></span>
			<h5 class="listing-aside-intro-title">{{ t('create_new_listing') }}</h5>
			<p class="listing-aside-intro-text">{{ t('do_you_have_something_text', ['appName' => config('app.name')]) }}</p>
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
	
	<div class="card listing-aside-tips">
		<div class="card-header listing-aside-tips-head">
			<span class="listing-aside-tips-icon"><i class="bi bi-lightbulb-fill"></i></span>
			<span>{{ t('how_to_sell_quickly') }}</span>
		</div>
		<div class="card-body text-start">
			<ul class="list-unstyled listing-aside-tips-list">
				<li><i class="bi bi-check-circle-fill"></i> <span>{{ t('sell_quickly_advice_1') }}</span></li>
				<li><i class="bi bi-check-circle-fill"></i> <span>{{ t('sell_quickly_advice_2') }}</span></li>
				<li><i class="bi bi-check-circle-fill"></i> <span>{{ t('sell_quickly_advice_3') }}</span></li>
				<li><i class="bi bi-check-circle-fill"></i> <span>{{ t('sell_quickly_advice_4') }}</span></li>
				<li><i class="bi bi-check-circle-fill"></i> <span>{{ t('sell_quickly_advice_5') }}</span></li>
			</ul>
		</div>
	</div>
	
</div>
