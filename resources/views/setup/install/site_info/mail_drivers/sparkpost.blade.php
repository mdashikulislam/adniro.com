{{-- sparkpost_secret --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sparkpost_secret'),
	'name'      => 'settings[mail][sparkpost_secret]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_secret'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
	'newline'   => true,
])

{{-- sparkpost_host --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sparkpost_host'),
	'name'      => 'settings[mail][sparkpost_host]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_host'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
])

{{-- sparkpost_port --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sparkpost_port'),
	'name'      => 'settings[mail][sparkpost_port]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_port'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
])

{{-- sparkpost_username --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sparkpost_username'),
	'name'      => 'settings[mail][sparkpost_username]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_username'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
])

{{-- sparkpost_password --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sparkpost_password'),
	'name'      => 'settings[mail][sparkpost_password]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_password'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
])

{{-- sparkpost_encryption --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sparkpost_encryption'),
	'name'      => 'settings[mail][sparkpost_encryption]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_encryption'),
	'hint'      => trans('messages.smtp_encryption_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
])

{{-- sparkpost_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][sparkpost_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.sparkpost_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sparkpost'],
])
