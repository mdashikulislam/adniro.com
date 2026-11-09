{{-- Cover image of the seller's public page (/user/{username}) --}}
@php
	$coverPath = $authUser->cover_path ?? null;
	$coverUrl = !empty($coverPath) ? fileUrl($coverPath) : null;
	$accountBaseUrl = url(urlGen()->getAccountBasePath());
	$publicProfileUrl = !empty($authUser->username ?? null) ? urlGen()->user(['username' => $authUser->username]) : null;
	$maxImageSize = (int)config('settings.upload.max_image_size', 1000);
@endphp
<div class="account-cover-card{{ !empty($coverUrl) ? ' has-cover' : '' }}"
     id="accountCoverCard"
     @if (!empty($coverUrl)) style="background-image: url('{{ $coverUrl }}');" @endif
>
	@if (empty($coverUrl))
		<span class="account-cover-orb account-cover-orb-1" aria-hidden="true"></span>
		<span class="account-cover-orb account-cover-orb-2" aria-hidden="true"></span>
	@endif
	
	<div class="account-cover-body">
		<div class="account-cover-text">
			<span class="account-cover-title"><i class="bi bi-image"></i> {{ t('cover_image') }}</span>
			<span class="account-cover-hint">{{ t('cover_image_hint') }}</span>
		</div>
		
		<div class="account-cover-actions">
			<form id="coverUploadForm"
			      action="{{ $accountBaseUrl . '/profile/cover' }}"
			      method="POST"
			      enctype="multipart/form-data"
			>
				@csrf
				<input type="file"
				       id="coverInput"
				       name="cover"
				       class="visually-hidden"
				       accept="image/jpeg,image/png,image/webp,image/gif"
				       data-max-kb="{{ $maxImageSize }}"
				       data-too-large="{{ trans('validation.max.file', ['attribute' => mb_strtolower(t('cover_image')), 'max' => $maxImageSize]) }}"
				>
				<button type="button" id="coverPickBtn" class="btn account-cover-btn account-cover-btn-light" data-uploading="{{ t('uploading') }}">
					<i class="bi bi-camera-fill"></i>
					<span>{{ !empty($coverUrl) ? t('change_cover') : t('upload_cover') }}</span>
				</button>
			</form>
			
			@if (!empty($coverUrl))
				<form action="{{ $accountBaseUrl . '/profile/cover/delete' }}" method="POST">
					@csrf
					<button type="submit" class="btn account-cover-btn account-cover-btn-ghost">
						<i class="bi bi-trash3"></i> <span>{{ t('remove_cover') }}</span>
					</button>
				</form>
			@endif
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		onDocumentReady((event) => {
			const cardEl = document.getElementById('accountCoverCard');
			const inputEl = document.getElementById('coverInput');
			const pickBtn = document.getElementById('coverPickBtn');
			const formEl = document.getElementById('coverUploadForm');
			if (!cardEl || !inputEl || !pickBtn || !formEl) {
				return;
			}
			
			pickBtn.addEventListener('click', () => inputEl.click());
			
			inputEl.addEventListener('change', () => {
				const file = inputEl.files && inputEl.files[0];
				if (!file) {
					return;
				}
				
				const maxKb = parseInt(inputEl.dataset.maxKb || '0', 10);
				if (maxKb > 0 && file.size > maxKb * 1024) {
					inputEl.value = '';
					jsAlert(inputEl.dataset.tooLarge, 'error', false);
					return;
				}
				
				{{-- Instant preview, then the form is sent --}}
				try {
					cardEl.style.backgroundImage = "url('" + URL.createObjectURL(file) + "')";
					cardEl.classList.add('has-cover', 'is-uploading');
				} catch (error) {
				}
				pickBtn.disabled = true;
				const labelEl = pickBtn.querySelector('span');
				if (labelEl) {
					labelEl.textContent = pickBtn.dataset.uploading;
				}
				formEl.submit();
			});
		});
	</script>
@endsection
