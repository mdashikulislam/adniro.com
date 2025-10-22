{{-- resend_api_key --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.resend_api_key'),
	'name'      => 'settings[mail][resend_api_key]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.resend_api_key'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'resend'],
])

{{-- resend_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][resend_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.resend_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'resend'],
])
