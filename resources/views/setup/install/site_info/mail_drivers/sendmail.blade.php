{{-- sendmail_path --}}
@php
	$sendmailPath = '/usr/sbin/sendmail -bs';
@endphp
@include('helpers.forms.fields.text', [
	'label'     => trans('messages.sendmail_path'),
	'name'      => 'settings[mail][sendmail_path]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.sendmail_path', $sendmailPath),
	'hint'      => trans('admin.sendmail_path_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sendmail'],
])

{{-- sendmail_email_sender --}}
@include('helpers.forms.fields.text', [
	'label'     => trans('admin.mail_email_sender_label'),
	'name'      => 'settings[mail][sendmail_email_sender]',
	'required'  => false,
	'value'     => data_get($siteInfo, 'settings.mail.sendmail_email_sender', data_get($siteInfo, 'user.email')),
	'hint'      => trans('admin.mail_email_sender_hint'),
	'baseClass' => ['wrapper' => 'mb-3 col-md-6'],
	'wrapper'   => ['class' => 'sendmail'],
])
