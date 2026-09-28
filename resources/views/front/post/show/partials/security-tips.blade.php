<div class="modal fade phone-modal" id="securityTips" tabindex="-1" role="dialog" aria-labelledby="securityTipsLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">

			<div class="modal-header phone-modal-header">
				<h5 class="modal-title phone-modal-title" id="securityTipsLabel">
					<span class="phone-modal-title-icon"><i class="bi bi-telephone-fill"></i></span>
					{{ t('phone_number') }}
				</h5>

				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>

			@php
				$phoneModal = '';
				$phoneModalLink = '';
				// If the 'hide_phone_number' option is disabled, append phone number in modal
				if (config('settings.listing_page.hide_phone_number') == '') {
					if (isset($post, $post->phone)) {
						$phoneModal = $post->phone;
						$phoneModalLink = 'tel:' . $post->phone;
					}
				}
			@endphp

			<div class="modal-body phone-modal-body">
				<div class="phone-modal-number-wrap">
					<span class="phone-modal-number-icon" aria-hidden="true"><i class="bi bi-phone-vibrate-fill"></i></span>
					<div id="phoneModal" class="phone-modal-number">{{ $phoneModal }}</div>
				</div>

				<div class="phone-modal-tips">
					<h3 class="phone-modal-tips-title">
						<span class="phone-modal-tips-icon"><i class="bi bi-shield-fill-exclamation"></i></span>
						{!! t('security_tips_title') !!}
					</h3>
					<div class="phone-modal-tips-text">
						{!! t('security_tips_text', ['appName' => config('app.name')]) !!}
					</div>
				</div>
			</div>

			<div class="modal-footer phone-modal-footer">
				<button type="button" class="btn btn-light phone-modal-close" data-bs-dismiss="modal">{{ t('Close') }}</button>
				<a id="phoneModalLink" href="{{ $phoneModalLink }}" class="btn btn-primary phone-modal-call">
					<i class="bi bi-telephone-outbound-fill"></i> {{ t('call_now') }}
				</a>
			</div>

		</div>
	</div>
</div>
