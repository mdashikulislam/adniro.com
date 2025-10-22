@php
	$baseColClass = isSingleStepFormEnabled() ? 'col-md-12 my-4' : 'col-md-10 col-sm-12 my-4';
@endphp
<div class="row d-none justify-content-center payment-plugin" id="paypalPayment">
	<div class="{{ $baseColClass }}">
		<div class="row">
			
			<div class="col-xl-12 text-center">
				<img class="img-fluid"
				     src="{{ url('plugins/paypal/images/payment.png') }}"
				     title="{{ trans('paypal::messages.payment_with') }}"
				     alt="{{ trans('paypal::messages.payment_with') }}"
				>
			</div>
			
			{{-- ... --}}
		
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		onDocumentReady((event) => {
			const params = {hasForm: false, hasLocalAction: false};
			
			loadPaymentGateway('paypal', params);
		});
	</script>
@endsection
