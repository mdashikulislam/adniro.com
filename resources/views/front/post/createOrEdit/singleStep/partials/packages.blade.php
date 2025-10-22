@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
	$noPackageOrPremiumOneSelected ??= true;
	
	$titlePaddingX = isSingleStepFormEnabled() ? ' px-2' : '';
@endphp
@if ($paymentMethods->count() > 0 && $noPackageOrPremiumOneSelected)
	@if (!empty($selectedPackage))
		
		<div class="my-4 col-md-12">
			<h5 class="w-100 mb-0 fw-bold fs-5 border rounded p-2">
				<i class="bi bi-wallet"></i> {{ t('Payment') }}
			</h5>
		</div>
		
		<div class="col-md-12 mb-4">
			<div class="container bg-body rounded p-2">
				
				<div class="row">
					<div class="col-sm-12">
						
						<div class="form-group mb-0">
							@include('front.payment.packages.selected')
						</div>
						
					</div>
				</div>
				
			</div>
		</div>
		
	@else
	
		@if ($packages->count() > 0)
			<div class="my-4 col-md-12">
				<h5 class="w-100 mb-0 fw-bold fs-5 border rounded p-2">
					<i class="bi bi-box"></i> {{ t('Packages') }}
				</h5>
			</div>
			
			<div class="col-md-12 mb-4">
				<div class="container bg-body rounded p-2">
					
					<div class="row">
						<div class="col-sm-12">
							@include('front.payment.packages')
						</div>
					</div>
					
				</div>
			</div>
		@endif
		
	@endif
@endif

@section('after_styles')
	@parent
@endsection

@section('after_scripts')
	@parent
	<script>
		const packageType = 'promotion';
		const formType = 'singleStep';
		const isCreationFormPage = {{ request()->segment(1) == 'create' ? 'true' : 'false' }};
	</script>
	@include('front.common.js.payment-js')
@endsection
