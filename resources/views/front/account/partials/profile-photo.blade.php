<div class="col-12">
	<div class="row">
		{{-- photo_path --}}
		@php
			$savedAvatar = [
				'key'  => $authUser->id ?? null,
				'path' => $authUser->photo_path ?? null,
				'url'  => $authUser->photo_url ?? null,
			];
			$uploadUrl = url(urlGen()->getAccountBasePath() . '/profile/photo');
			$deleteUrlPattern = url(urlGen()->getAccountBasePath() . '/profile/photo/delete');
		@endphp
		@include('helpers.forms.fields.fileinput-ajax-avatar', [
			'name'       => 'photo_path',
			'label'      => t('Photo or Avatar'),
			'labelClass' => 'fw-bold',
			'value'      => $savedAvatar,
			'pluginOptions' => [
				'uploadUrl'       => $uploadUrl,
				'uploadExtraData' => [
					'_token'  => csrf_token(),
					'_method' => 'PUT'
				],
				'elSuccessContainer' => '#avatarUploadSuccess',
			],
			'deleteUrlPattern'  => $deleteUrlPattern,
			'elTargetContainer' => '#userImg',
			'baseClass'         => ['wrapper' => 'col-md-12'],
		])
	</div>
</div>
