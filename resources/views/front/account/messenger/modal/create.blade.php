@php
	$post ??= [];
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
	$allowedFileFormatsJson = collect(getAllowedFileFormats())->toJson();
	
	$actionUrl =url(urlGen()->getAccountBasePath() . '/messages/posts/' . data_get($post, 'id'));
@endphp
<form action="{{ $actionUrl }}" method="POST" enctype="multipart/form-data" role="form">
	@csrf
	@honeypot
	<div class="modal fade" id="contactUser" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable">
			<div class="modal-content">
				
				<div class="modal-header px-3">
					<h4 class="modal-title fs-5 fw-bold">
						<i class="bi bi-envelope"></i> {{ t('contact_advertiser') }}
					</h4>
					
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
				</div>
				
				<div class="modal-body">
					@if (isset($errors) && $errors->any() && old('messageForm')=='1')
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<ul class="mb-0 list-unstyled">
								@foreach($errors->all() as $error)
									<li class="lh-lg"><i class="bi bi-check-lg me-1"></i>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					
					<input type="hidden" name="country_code" value="{{ config('country.code') }}">
					<input type="hidden" name="post_id" value="{{ data_get($post, 'id') }}">
					<input type="hidden" name="messageForm" value="1">
					
					<div class="row d-flex justify-content-center">
						<div class="col-md-10 col-sm-12 col-xs-12">
							<div class="row">
								@php
									$authUser = auth()->check() ? auth()->user() : null;
									$isNameCanBeHidden = (!empty($authUser));
									$isEmailCanBeHidden = (!empty($authUser) && !empty($authUser->email));
									$isPhoneCanBeHidden = (!empty($authUser) && !empty($authUser->phone));
									$authFieldValue = data_get($post, 'auth_field', getAuthField());
								@endphp
								
								{{-- name --}}
								@if ($isNameCanBeHidden)
									<input type="hidden" name="name" value="{{ $authUser->name ?? null }}">
								@else
									@include('helpers.forms.fields.text', [
										'label'       => t('Name'),
										'id'          => 'fromName',
										'name'        => 'name',
										'placeholder' => t('enter_your_name'),
										'required'    => true,
										'value'       => $authUser->name ?? null,
									])
								@endif
								
								{{-- email --}}
								@if ($isEmailCanBeHidden)
									<input type="hidden" name="email" value="{{ $authUser->email ?? null }}">
								@else
									@include('helpers.forms.fields.email', [
										'label'       => trans('auth.email'),
										'id'          => 'fromEmail',
										'name'        => 'email',
										'required'    => ($authFieldValue == 'email'),
										'placeholder' => t('enter_your_email'),
										'value'       => $authUser->email ?? null,
										'attributes'  => ['data-valid-type' => 'email'],
										'prefix'      => '<i class="fa-regular fa-envelope"></i>',
										'suffix'      => null,
										'baseClass'   => ['wrapper' => 'mb-3 col-lg-12'],
									])
								@endif
								
								{{-- phone --}}
								@if ($isPhoneCanBeHidden)
									<input type="hidden" name="phone" value="{{ $authUser->phone ?? null }}">
									<input name="phone_country" type="hidden" value="{{ $authUser->phone_country ?? config('country.code') }}">
								@else
									@php
										$phoneValue = $authUser->phone ?? null;
										$phoneCountryValue = $authUser->phone_country ?? config('country.code');
										$phoneRequiredClass = ($authFieldValue == 'phone') ? ' required' : '';
									@endphp
									@include('helpers.forms.fields.intl-tel-input', [
										'label'       => trans('auth.phone_number'),
										'id'          => 'fromPhone',
										'name'        => 'phone',
										'required'    => ($authFieldValue == 'phone'),
										'placeholder' => trans('auth.phone_number'),
										'value'       => $phoneValue,
										'attributes'  => ['maxlength' => 60],
										'countryCode' => $phoneCountryValue,
										'baseClass'   => ['wrapper' => 'mb-3 col-lg-12'],
									])
								@endif
								
								{{-- auth_field --}}
								<input name="auth_field" type="hidden" value="{{ $authFieldValue }}">
								
								{{-- body --}}
								@include('helpers.forms.fields.textarea', [
									'label'       => t('Message') . ' <span class="text-count">(500 max)</span>',
									'id'          => 'body',
									'name'        => 'body',
									'placeholder' => t('enter_your_message'),
									'required'    => true,
									'value'       => null,
									'default'     => t('is_still_available', ['name' => data_get($post, 'contact_name', t('sir_miss'))]),
									'attributes'    => ['rows' => 5],
									'pluginOptions' => ['height' => 150],
								])
								
								{{-- file_path --}}
								@php
									$catType = data_get($post, 'category.parent.type', data_get($post, 'category.type'));
								@endphp
								@if ($catType == 'job-offer')
									@include('helpers.forms.fields.fileinput', [
										'label' => t('Resume'),
										'name'  => 'file_path',
									])
									<input type="hidden" name="catType" value="{{ $catType }}">
								@endif
								
								{{-- captcha --}}
								@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
							</div>
						</div>
					</div>
					
				</div>
				
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary float-end">{{ t('send_message') }}</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ t('Cancel') }}</button>
				</div>
			</div>
		</div>
	</div>
</form>
@section('after_styles')
	@parent
@endsection

@section('after_scripts')
    @parent
	<script>
		@if (auth()->check())
			phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
		@endif
		
		onDocumentReady((event) => {
			{{-- Re-open the modal if error occured --}}
			@if ($errors->any())
				@if ($errors->any() && old('messageForm') == '1')
					const contactUserEl = document.getElementById('contactUser');
					if (contactUserEl) {
						const contactUserModal = new bootstrap.Modal(contactUserEl, {});
						contactUserModal.show();
					}
				@endif
			@endif
		});
	</script>
@endsection
