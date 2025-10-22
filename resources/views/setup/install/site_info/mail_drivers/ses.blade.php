{{-- ses_key --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_key'),
	'name'      => 'settings[mail][ses_key]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_key'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_secret --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_secret'),
	'name'      => 'settings[mail][ses_secret]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_secret'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_region --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_region'),
	'name'      => 'settings[mail][ses_region]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_region'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_token --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_token'),
	'name'      => 'settings[mail][ses_token]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_token'),
	'hint'      => trans('messages.ses_token_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_host --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_host'),
	'name'      => 'settings[mail][ses_host]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_host'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_port --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_port'),
	'name'      => 'settings[mail][ses_port]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_port'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_username --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_username'),
	'name'      => 'settings[mail][ses_username]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_username'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_password --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_password'),
	'name'      => 'settings[mail][ses_password]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_password'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_encryption --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.ses_encryption'),
	'name'      => 'settings[mail][ses_encryption]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.ses_encryption'),
	'hint'      => trans('messages.smtp_encryption_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])

{{-- ses_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][ses_email_sender]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailersend_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'ses'],
])
