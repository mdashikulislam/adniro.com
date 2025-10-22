{{-- mailersend_api_key --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.mailersend_api_key'),
	'name'      => 'settings[mail][mailersend_api_key]',
	'required'  => true,
	'value'     => data_get($siteInfo, 'settings.mail.mailersend_api_key'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailersend'],
])

{{-- mailersend_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][mailersend_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.mailersend_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'mailersend'],
])
