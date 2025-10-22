@php
	$authUserIsAdmin ??= false;
	$providers ??= [];
@endphp
<div class="col-12">
	<div class="card">
		<div class="card-header">
			<h5 class="card-title mb-0">
				{{ trans('auth.connected_accounts') }}
			</h5>
		</div>
		<div class="card-body">
			@if (!empty($providers))
				<form action="{{ urlGen()->accountLinkedAccounts() }}" method="POST">
					{!! csrf_field() !!}
					<input name="_method" type="hidden" value="DELETE">
					<table class="table">
						<thead>
						<tr>
							<th scope="col" style="width: 10%">#</th>
							<th scope="col" style="width: 40%">{{ trans('auth.service') }}</th>
							<th scope="col" style="width: 40%">{{ t('Date') }}</th>
							<th scope="col" style="width: 10%">{{ t('action') }}</th>
						</tr>
						</thead>
						<tbody>
						@foreach($providers as $provider => $providerData)
							@php
								$btnClass = data_get($providerData, 'btnClass');
								$iconClass = data_get($providerData, 'iconClass');
								$url = data_get($providerData, 'url');
								$name = data_get($providerData, 'name');
								$label = data_get($providerData, 'label');
								$title = strip_tags($label);
								$isConnected = data_get($providerData, 'isConnected');
								$connectedAt = data_get($providerData, 'connectedAt');
								
								$name = $isConnected ? $label : '<strong>' . $name . '</strong>';
								// $actionBtnLabel = $isConnected ? trans('auth.disconnect') : trans('auth.connect');
								$actionBtnLabel = trans('auth.disconnect');
								$disableClass = !$isConnected ? ' disabled' : '';
							@endphp
							<tr>
								<th scope="row">
									<i class="{{ $iconClass }}"></i>
								</th>
								<td>{!! $name !!}</td>
								<td>{!! $connectedAt !!}</td>
								<td>
									<a href="{{ urlGen()->accountDisconnectLinkedAccount($provider) }}"
									   class="btn btn-sm btn-secondary{{ $disableClass }}"
									>
										{{ $actionBtnLabel }}
									</a>
								</td>
							</tr>
						@endforeach
						</tbody>
					</table>
				</form>
			@else
				<idv class="row m-5">
					<div class="col-12 text-muted fs-6 d-flex justify-content-center">
						{{ trans('auth.no_connected_accounts') }}
					</div>
				</idv>
			@endif
		</div>
	</div>
</div>
