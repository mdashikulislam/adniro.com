{{--
	adniro: "Email the seller", for a listing posted without a member account, which cannot be chatted with.
	The site sends the message; the seller's address is never shown. Members only (public/assets/js/app/email-seller.js).
--}}
@php
	$emailSellerMin = \App\Http\Requests\Front\Chat\EmailSellerRequest::MESSAGE_MIN;
	$emailSellerMax = \App\Http\Requests\Front\Chat\EmailSellerRequest::MESSAGE_MAX;
@endphp
<div class="modal fade" id="adnEmailSeller" tabindex="-1" aria-labelledby="adnEmailSellerTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<form class="modal-content" novalidate
		      data-email-seller
		      data-url="{{ route('chat.email-seller', ['post' => data_get($post, 'id')]) }}"
		      data-sent="{{ trans('chat.email_seller_sent') }}"
		      data-failed="{{ trans('chat.email_seller_failed') }}"
		      data-min="{{ $emailSellerMin }}"
		      data-too-short="{{ trans('chat.error_empty_email') }}"
		      data-too-fast="{{ trans('chat.error_too_fast') }}">
			<div class="modal-header">
				<h2 class="modal-title fs-5 fw-bold" id="adnEmailSellerTitle">{{ trans('chat.email_seller') }}</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('chat.report_cancel') }}"></button>
			</div>
			<div class="modal-body">
				<p class="text-secondary small mb-3">{{ trans('chat.email_seller_intro') }}</p>
				<label class="form-label small fw-semibold" for="adnEmailSellerMessage">{{ trans('chat.email_seller_label') }}</label>
				<textarea class="form-control" id="adnEmailSellerMessage" name="message" rows="5" maxlength="{{ $emailSellerMax }}" required
				          aria-describedby="adnEmailSellerShare"></textarea>
				<p class="text-secondary small mt-2 mb-0" id="adnEmailSellerShare">
					<i class="bi bi-info-circle" aria-hidden="true"></i> {{ trans('chat.email_seller_share') }}
				</p>
				<p class="text-danger small mt-2 mb-0" data-email-seller-error role="alert" hidden></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ trans('chat.report_cancel') }}</button>
				<button type="submit" class="btn btn-primary" data-email-seller-send>
					<i class="bi bi-send" aria-hidden="true"></i> {{ trans('chat.email_seller_send') }}
				</button>
			</div>
		</form>
	</div>
</div>

@push('after_scripts_stack')
	<script src="{{ url('assets/js/app/email-seller.js') }}?v={{ @filemtime(public_path('assets/js/app/email-seller.js')) }}" defer></script>
@endpush
