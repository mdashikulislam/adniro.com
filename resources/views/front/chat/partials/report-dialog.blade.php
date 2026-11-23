{{-- adniro: report a conversation to the admins --}}
<div class="modal fade" id="adnChatReport" tabindex="-1" aria-labelledby="adnChatReportTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<form class="modal-content adn-chat-report" data-chat-report-form novalidate>
			<div class="modal-header">
				<h2 class="modal-title fs-5 fw-bold" id="adnChatReportTitle">{{ trans('chat.report_title') }}</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('chat.report_cancel') }}"></button>
			</div>
			<div class="modal-body">
				<p class="text-secondary small mb-3">{{ trans('chat.report_intro') }}</p>
				<fieldset class="adn-chat-report-reasons">
					<legend class="visually-hidden">{{ trans('chat.report_title') }}</legend>
					@foreach (\App\Models\Chat\ThreadReport::REASONS as $reason)
						<label class="adn-chat-report-reason">
							<input type="radio" name="reason" value="{{ $reason }}" class="form-check-input">
							<span>{{ trans('chat.js.reason_' . $reason) }}</span>
						</label>
					@endforeach
				</fieldset>
				<label class="form-label small fw-semibold mt-3" for="adnChatReportNote">{{ trans('chat.report_note_label') }}</label>
				<textarea class="form-control" id="adnChatReportNote" name="note" rows="3" maxlength="500"></textarea>
				<p class="adn-chat-error mt-2 mb-0" data-chat-report-error role="alert" hidden></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ trans('chat.report_cancel') }}</button>
				<button type="submit" class="btn btn-danger" data-chat-report-send>{{ trans('chat.report_send') }}</button>
			</div>
		</form>
	</div>
</div>
