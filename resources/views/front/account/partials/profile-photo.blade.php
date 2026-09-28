<div class="account-avatar-card">
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
			'labelClass' => 'visually-hidden',
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
		
		@php
			$profileName = $authUser->name ?? '--';
			$profileHandle = !empty($authUser->username ?? null) ? '@' . $authUser->username : null;
			$profileEmail = $authUser->email ?? null;
			$profileVerified = !empty($authUser->email_verified_at ?? null) || !empty($authUser->phone_verified_at ?? null);
			try {
				$profileSince = !empty($authUser->created_at) ? \Carbon\Carbon::parse($authUser->created_at)->translatedFormat('M Y') : null;
			} catch (\Throwable $e) {
				$profileSince = null;
			}
		@endphp
		<div class="col-md-12">
			<div class="account-avatar-meta">
				<span class="account-avatar-name">{{ $profileName }}</span>
				@if (!empty($profileHandle))
					<span class="account-avatar-handle">{{ $profileHandle }}</span>
				@endif
				<div class="account-avatar-chips">
					@if ($profileVerified)
						<span class="account-avatar-chip is-verified"><i class="bi bi-patch-check-fill"></i> {{ t('Verified') }}</span>
					@endif
					@if (!empty($profileSince))
						<span class="account-avatar-chip"><i class="bi bi-calendar3"></i> {{ t('Joined') }} {{ $profileSince }}</span>
					@endif
				</div>
				@if (!empty($profileEmail))
					<span class="account-avatar-email"><i class="bi bi-envelope"></i> {{ $profileEmail }}</span>
				@endif
			</div>
		</div>
	</div>
</div>
