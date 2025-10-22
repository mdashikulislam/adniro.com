@php
	$paymentMethods ??= collect();
	$payment ??= [];
@endphp
<div class="row mb-0">
	{{-- payment_method_id --}}
	@php
		$paymentMethodOptions = collect($paymentMethods)
			->filter(fn($paymentMethod) => view()->exists('payment::' . $paymentMethod->name))
			->map(function($paymentMethod) {
				$dataName = $paymentMethod->name ?? null;
				$value = $paymentMethod->id ?? null;
				$text = ($dataName == 'offlinepayment')
					? trans('offlinepayment::messages.offline_payment')
					: ($paymentMethod->display_name ?? null);
				
				return [
					'value'      => $value,
					'text'       => $text,
					'attributes' => ['data-name' => $dataName],
				];
			})->toArray();
		
		$paymentMethodValue = data_get($payment, 'paymentMethod.id', 0);
	@endphp
	@include('helpers.forms.fields.select2', [
		'label'       => t('payment_method'),
		'id'          => 'paymentMethodId',
		'name'        => 'payment_method_id',
		'required'    => false,
		'placeholder' => t('Select'),
		'options'     => $paymentMethodOptions,
		'hint'        => null,
		'baseClass'   => ['wrapper' => 'mb-3 col-md-12 col-sm-12 py-0'],
	])
</div>
