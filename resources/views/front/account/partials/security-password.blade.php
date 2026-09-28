@php
	$passwordTips = getPasswordTips();
@endphp
<div class="col-12">
	<div class="card account-card">
		<div class="card-header account-card-head">
			<span class="account-card-icon"><i class="bi bi-key-fill"></i></span>
			<h5 class="card-title mb-0">{{ trans('auth.change_password') }}</h5>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12">
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
								'baseClass'      => ['wrapper' => 'mb-3 col-md-12'],
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
								'baseClass'      => ['wrapper' => 'mb-3 col-md-6'],
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
								'baseClass'      => ['wrapper' => 'mb-3 col-md-6'],
							])
							
							{{-- button --}}
							<div class="col-12 mt-2 account-form-actions">
								<button type="submit" class="btn btn-primary account-submit-btn">
									<i class="bi bi-shield-lock"></i> {{ t('Update') }}
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
