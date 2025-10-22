@php
	$passwordTips = getPasswordTips();
@endphp
<div class="col-12">
	<div class="card">
		<div class="card-header">
			<h5 class="card-title mb-0">
				{{ trans('auth.change_password') }}
			</h5>
		</div>
		<div class="card-body">
			<div class="row d-flex justify-content-center">
				<div class="col-xl-7 col-lg-8 col-md-10 col-sm-12">
					<form name="passwordForm" action="{{ urlGen()->accountSecurityPassword() }}" method="POST" role="form">
						@csrf
						@method('PUT')
						
						<input name="user_id" type="hidden" value="{{ $authUser->getAuthIdentifier() }}">
						
						<div class="row">
							{{-- current_password --}}
							@include('helpers.forms.fields.password', [
								'label'          => trans('auth.current_password'),
								'id'             => 'currentPassword',
								'name'           => 'current_password',
								'placeholder'    => trans('auth.current_password'),
								'required'       => true,
								'value'          => null,
								'togglePassword' => 'link',
								'hint'           => '',
							])
							
							{{-- new_password --}}
							@include('helpers.forms.fields.password', [
								'label'          => trans('auth.new_password'),
								'id'             => 'newPassword',
								'name'           => 'new_password',
								'placeholder'    => trans('auth.new_password'),
								'required'       => true,
								'value'          => null,
								'togglePassword' => 'link',
							])
							
							{{-- new_password_confirmation --}}
							@include('helpers.forms.fields.password', [
								'label'          => trans('auth.confirm_new_password'),
								'id'             => 'newPasswordConfirmation',
								'name'           => 'new_password_confirmation',
								'placeholder'    => trans('auth.confirm_new_password'),
								'required'       => true,
								'value'          => null,
								'togglePassword' => 'link',
								'hint'           => '',
							])
							
							{{-- button --}}
							<div class="col-12 mt-3">
								<div class="row">
									<div class="col-md-12">
										<button type="submit" class="btn btn-primary">{{ t('Update') }}</button>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
