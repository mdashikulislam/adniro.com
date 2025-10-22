{{-- smtp_host --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.smtp_host'),
	'name'      => 'settings[mail][smtp_host]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.smtp_host'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'smtp'],
])

{{-- smtp_port --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.smtp_port'),
	'name'      => 'settings[mail][smtp_port]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.smtp_port'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'smtp'],
])

{{-- smtp_username --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.smtp_username'),
	'name'      => 'settings[mail][smtp_username]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.smtp_username'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'smtp'],
])

{{-- smtp_password --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.smtp_password'),
	'name'      => 'settings[mail][smtp_password]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.smtp_password'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'smtp'],
])

{{-- smtp_encryption --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.smtp_encryption'),
	'name'      => 'settings[mail][smtp_encryption]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.smtp_encryption'),
	'hint'      => trans('messages.smtp_encryption_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'smtp'],
])

{{-- smtp_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][smtp_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.smtp_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'smtp'],
])
