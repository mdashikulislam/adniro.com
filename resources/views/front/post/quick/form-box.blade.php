{{-- The AI box in the listing form (adniro:ai), ported from classifedz.com: describe the item once, typed or spoken, and the form is filled in place for review. --}}
@php
	$aiBoxUser = auth()->user();
	$aiBoxService = app(\App\Services\QuickPost\QuickPostService::class);
	$aiBoxOn = $aiBoxService->available($aiBoxUser);
	$aiBoxLogin = !$aiBoxOn && is_null($aiBoxUser) && app(\App\Services\Ai\AiSettings::class)->quickpostEnabled();
@endphp
@if ($aiBoxOn || $aiBoxLogin)
	@php
		$aiBoxLimit = $aiBoxService->limitStatus($aiBoxUser, request()->ip());
		$aiBoxLimitText = \App\Http\Controllers\Web\Front\Post\QuickPost\QuickPostController::limitText($aiBoxLimit);
		$aiBoxNoneLeft = $aiBoxOn && $aiBoxLimit->reached();
		$aiBoxServerVoice = $aiBoxOn && $aiBoxService->serverVoiceAvailable($aiBoxUser);
		$aiBoxSpeech = \App\Http\Controllers\Web\Front\Post\QuickPost\QuickPostController::voiceLanguages();
		$aiBoxStrings = collect([
			'fill', 'filling', 'listening', 'speak', 'rec_pause', 'rec_continue', 'rec_short', 'too_short', 'confirm_replace',
			'restored', 'error', 'done', 'row_category', 'row_no_category', 'row_title', 'row_no_title', 'row_price', 'row_no_price',
			'row_details', 'row_check_details', 'row_city', 'row_city_unknown', 'row_no_city', 'row_tags', 'row_heard',
		])->mapWithKeys(fn ($key) => [$key => trans('ai.box_' . $key)])->merge([
			'timeout'     => trans('ai.qp_timeout'),
			'mic_denied'  => trans('ai.qp_voice_denied'),
			'no_speech'   => trans('ai.qp_voice_no_speech'),
		])->all();
		$aiBoxExamples = ['phone', 'car', 'home', 'furniture'];
	@endphp
	<div class="col-12 mb-4 ai-quick-wrap">
		<div class="ai-quick card" id="aiQuick"
		     data-url="{{ url('posts/create/quick/fill') }}"
		     data-wysiwyg="{{ in_array(config('settings.listing_form.wysiwyg_editor'), ['none', '', null], true) ? '0' : '1' }}"
		     data-selection-url="{{ url('browsing/categories/select') }}"
		     data-max-seconds="{{ (int)config('ai_features.quickpost.max_audio_seconds', 60) }}"
		     data-server-voice="{{ $aiBoxServerVoice ? '1' : '0' }}"
		     data-speech-lang="{{ $aiBoxSpeech['default'] }}"
		     data-strings="{{ json_encode($aiBoxStrings, JSON_UNESCAPED_UNICODE) }}"
		>
			<div class="card-body">
				<span class="ai-quick-badge"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ trans('ai.box_badge') }}</span>
				<h2 class="ai-quick-title">{{ trans('ai.box_title') }}</h2>
				<p class="ai-quick-hint">{{ trans('ai.box_hint') }}</p>

				@if ($aiBoxLogin)
					<div class="d-flex flex-wrap align-items-center gap-3">
						<a href="#quickLogin" data-bs-toggle="modal" class="btn ai-quick-fill">
							<i class="fa-solid fa-right-to-bracket"></i> {{ trans('ai.box_login') }}
						</a>
						<span class="small text-secondary">{{ trans('ai.box_login_or') }}</span>
					</div>
				@else
					<div class="ai-quick-field">
						<label for="aiQuickText" class="visually-hidden">{{ trans('ai.box_label') }}</label>
						<textarea id="aiQuickText" class="form-control ai-quick-text" rows="3"
						          maxlength="{{ (int)config('ai_features.quickpost.max_text', 2000) }}"
						          placeholder="{{ trans('ai.qp_text_placeholder') }}"
						          @disabled($aiBoxNoneLeft)></textarea>
						<button type="button" class="btn ai-quick-speak d-none" @disabled($aiBoxNoneLeft)
						        title="{{ trans('ai.box_speak') }}" aria-label="{{ trans('ai.box_speak') }}">
							<i class="fa-solid fa-microphone"></i>
						</button>
					</div>

					<div class="ai-quick-chips">
						<span>{{ trans('ai.box_try') }}</span>
						@foreach ($aiBoxExamples as $example)
							<button type="button" class="ai-quick-chip" data-example="{{ trans('ai.box_example_' . $example) }}">{{ trans('ai.box_chip_' . $example) }}</button>
						@endforeach
					</div>

					{{-- Voice bar while recording --}}
					<div class="ai-quick-recording d-none" role="status" aria-live="polite">
						<div class="ai-voice-bar">
							<button type="button" class="ai-voice-cancel ai-quick-cancel" aria-label="{{ trans('ai.box_rec_delete') }}" title="{{ trans('ai.box_rec_delete') }}">
								<i class="fa-regular fa-trash-can"></i>
							</button>
							<div class="ai-voice-track">
								<span class="ai-voice-dot" aria-hidden="true"></span>
								<span class="ai-quick-timer">0:00</span>
								<canvas class="ai-voice-wave" aria-hidden="true"></canvas>
							</div>
							<button type="button" class="ai-voice-pause" aria-label="{{ trans('ai.box_rec_pause') }}" title="{{ trans('ai.box_rec_pause') }}">
								<i class="fa-solid fa-pause"></i>
							</button>
							<button type="button" class="ai-voice-send ai-quick-stop" aria-label="{{ trans('ai.box_rec_send') }}" title="{{ trans('ai.box_rec_send') }}">
								<i class="fa-solid fa-paper-plane"></i>
								<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
							</button>
						</div>
						<p class="ai-voice-hint">
							<span class="ai-voice-hint-rec">{{ trans('ai.box_rec_hint') }}</span>
							<span class="ai-voice-hint-paused">{{ trans('ai.box_rec_paused') }}</span>
							<span class="ai-voice-hint-send">{{ trans('ai.box_rec_sending') }}</span>
						</p>
					</div>

					<div class="ai-quick-footer">
						<div class="d-flex flex-wrap align-items-center gap-2">
							@if ($aiBoxNoneLeft)
								<span class="ai-quick-usage">{{ trans('ai.qp_limit_reached', ['when' => $aiBoxLimit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL') ?? '']) }}</span>
							@elseif ($aiBoxLimitText)
								<span class="ai-quick-usage"><i class="fa-solid fa-bolt text-warning"></i> <span class="ai-quick-usage-text">{{ $aiBoxLimitText }}</span></span>
							@endif
							<button type="button" class="btn btn-link btn-sm ai-quick-undo d-none">
								<i class="fa-solid fa-rotate-left"></i> {{ trans('ai.box_undo') }}
							</button>
						</div>
						<button type="button" class="btn ai-quick-fill" @disabled($aiBoxNoneLeft)>
							<i class="fa-solid fa-wand-magic-sparkles"></i> <span class="ai-quick-fill-label">{{ trans('ai.box_fill') }}</span>
						</button>
					</div>

					<div class="ai-quick-message small mt-2 d-none" role="status" aria-live="polite"></div>
					<div class="ai-quick-result mt-3 d-none" aria-live="polite"></div>
					<p class="small text-secondary mt-2 mb-0">{{ trans('ai.box_privacy') }}</p>
				@endif
			</div>
		</div>
	</div>

	@pushOnce('after_styles_stack', 'ai_formbox_css')
		<link href="{{ url('assets/css/ai-formbox.css') }}?v=1" rel="stylesheet">
	@endPushOnce
	@if ($aiBoxOn)
		@pushOnce('after_scripts_stack', 'ai_formbox_js')
			<script src="{{ url('assets/js/app/ai.formbox.js') }}?v=1"></script>
		@endPushOnce
	@endif
@endif
