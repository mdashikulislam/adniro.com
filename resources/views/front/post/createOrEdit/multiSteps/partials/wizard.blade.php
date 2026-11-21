@section('body_class', 'page-listing-form')
@if (!empty($wizardMenu))
	@php
		// Keep only the included steps, then work out where the user is
		$wizardSteps = collect($wizardMenu)->filter(fn ($menu) => !empty($menu['included']))->values();
		$activeIndex = $wizardSteps->search(fn ($menu) => str_contains((string)($menu['class'] ?? ''), 'active'));
		$activeIndex = ($activeIndex === false) ? -1 : $activeIndex;

		$stepIcon = function ($key, $label) {
			$key = strtolower($key . ' ' . $label);
			return match (true) {
				str_contains($key, 'photo') || str_contains($key, 'picture') => 'bi bi-images',
				str_contains($key, 'payment') || str_contains($key, 'package') => 'bi bi-credit-card-2-front',
				str_contains($key, 'verify') || str_contains($key, 'activation') => 'bi bi-shield-check',
				str_contains($key, 'finish') => 'bi bi-flag-fill',
				default => 'bi bi-card-text',
			};
		};
		$menuKeys = collect($wizardMenu)->filter(fn ($menu) => !empty($menu['included']))->keys()->values();
		$lastIndex = $wizardSteps->count() - 1;
	@endphp
	@include('front.common.spacer')
	<div class="container">
		<div class="row">
			<div class="col-12 mt-md-1 mt-sm-0 mt-0">
				<ol class="listing-steps" aria-label="{{ t('Step') }}">
					@foreach($wizardSteps as $index => $menu)
						@php
							$state = match (true) {
								$index === $activeIndex => 'is-active',
								$index < $activeIndex => 'is-done',
								default => 'is-pending',
							};
							$stepClass = trim((string)($menu['class'] ?? ''));
							$stepClass = str_replace('active', '', $stepClass);
							$stepUrl = $menu['url'] ?? null;
							$stepLabel = $menu['label'] ?? '--';
							$icon = $stepIcon($menuKeys[$index] ?? '', $stepLabel);
							$isClickable = !empty($stepUrl) && $state !== 'is-active';
						@endphp
						<li class="listing-step {{ $state }} {{ $stepClass }}">
							@if ($isClickable)
								<a class="listing-step-link" href="{{ $stepUrl }}">
							@else
								<span class="listing-step-link"{!! $state === 'is-active' ? ' aria-current="step"' : '' !!}>
							@endif
								<span class="listing-step-dot">
									@if ($state === 'is-done')
										<i class="bi bi-check-lg"></i>
									@else
										<i class="{{ $icon }}"></i>
									@endif
								</span>
								<span class="listing-step-text">
									<span class="listing-step-caption">{{ t('Step') }} {{ $index + 1 }}</span>
									<span class="listing-step-label">{{ $stepLabel }}</span>
								</span>
							@if ($isClickable)
								</a>
							@else
								</span>
							@endif
							@if ($index < $lastIndex)
								<span class="listing-step-line" aria-hidden="true"></span>
							@endif
						</li>
					@endforeach
				</ol>
				@if (config('listing_form.v2') && $activeIndex >= 0)
					{{-- adniro:ai - phones: "Step 1 of 3 · Details" and a progress bar (the list above stays for screen readers) --}}
					@php
						$stepTotal = $wizardSteps->count();
						$stepPercent = (int)round(($activeIndex + 1) / max($stepTotal, 1) * 100);
					@endphp
					<div class="listing-steps-compact" aria-hidden="true">
						<p class="listing-steps-compact-text">
							<span>{{ trans('listing_form.step_of', ['current' => $activeIndex + 1, 'total' => $stepTotal]) }}</span>
							· <strong>{{ $wizardSteps[$activeIndex]['label'] ?? '' }}</strong>
						</p>
						<div class="listing-steps-compact-bar"><span style="width: {{ $stepPercent }}%"></span></div>
					</div>
					@pushOnce('after_styles_stack', 'listing_form_css')
						<link href="{{ url('assets/css/listing-form.css') }}?v={{ @filemtime(public_path('assets/css/listing-form.css')) }}" rel="stylesheet">
					@endPushOnce
				@endif
			</div>
		</div>
	</div>
@endif
