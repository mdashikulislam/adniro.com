{{-- mailgun_domain --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_domain'),
	'name'      => 'settings[mail][mailgun_domain]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_domain'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_secret --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_secret'),
	'name'      => 'settings[mail][mailgun_secret]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_secret'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_endpoint --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_endpoint'),
	'name'      => 'settings[mail][mailgun_endpoint]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_endpoint', 'api.mailgun.net'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
	'newline'   => true,
])

{{-- mailgun_host --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_host'),
	'name'      => 'settings[mail][mailgun_host]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_host'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_port --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_port'),
	'name'      => 'settings[mail][mailgun_port]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_port'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_username --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_username'),
	'name'      => 'settings[mail][mailgun_username]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_username'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_password --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_password'),
	'name'      => 'settings[mail][mailgun_password]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_password'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_encryption --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailgun_encryption'),
	'name'      => 'settings[mail][mailgun_encryption]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_encryption'),
	'hint'      => trans('messages.smtp_encryption_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])

{{-- mailgun_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][mailgun_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.mailgun_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailgun'],
])
