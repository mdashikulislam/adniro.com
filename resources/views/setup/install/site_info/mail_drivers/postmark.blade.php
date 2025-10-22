{{-- postmark_token --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.postmark_token'),
	'name'      => 'settings[mail][postmark_token]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_token'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
	'newline'   => true,
])

{{-- postmark_host --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.postmark_host'),
	'name'      => 'settings[mail][postmark_host]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_host'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
])

{{-- postmark_port --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.postmark_port'),
	'name'      => 'settings[mail][postmark_port]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_port'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
])

{{-- postmark_username --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.postmark_username'),
	'name'      => 'settings[mail][postmark_username]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_username'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
])

{{-- postmark_password --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.postmark_password'),
	'name'      => 'settings[mail][postmark_password]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_password'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
])

{{-- postmark_encryption --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.postmark_encryption'),
	'name'      => 'settings[mail][postmark_encryption]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_encryption'),
	'hint'      => trans('messages.smtp_encryption_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
])

{{-- postmark_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][postmark_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.postmark_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'postmark'],
])
