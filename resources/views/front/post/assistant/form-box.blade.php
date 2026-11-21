{{-- The AI box in the listing form (adniro:ai), ported from classifedz.com: describe the item once, typed or spoken, and the form is filled in place for review. --}}
@php
	$aiBoxUser = auth()->user();
	$aiBoxService = app(\App\Services\ListingAssistant\ListingAssistantService::class);
	$aiBoxOn = $aiBoxService->available($aiBoxUser);
	$aiBoxLogin = !$aiBoxOn && is_null($aiBoxUser) && app(\App\Services\Ai\AiSettings::class)->listingAssistantEnabled();
@endphp
@if ($aiBoxLogin && ($lfV2 ?? false))
	{{-- Redesigned listing form: guests get one dismissible line instead of the whole box --}}
	<div class="lf-ai-banner" data-lf-ai-banner>
		<i class="fa-solid fa-wand-magic-sparkles lf-ai-banner-icon" aria-hidden="true"></i>
		<span class="lf-ai-banner-text">{{ trans('listing_form.ai_guest_text') }}</span>
		<a href="#quickLogin" data-bs-toggle="modal" class="lf-ai-banner-login">{{ trans('listing_form.ai_guest_login') }}</a>
		<button type="button" class="btn-close lf-ai-banner-hide" data-lf-ai-banner-hide
		        aria-label="{{ trans('listing_form.ai_guest_hide') }}"></button>
	</div>
@elseif ($aiBoxOn || $aiBoxLogin)
	@php
		$aiBoxLimit = $aiBoxService->limitStatus($aiBoxUser, request()->ip());
		$aiBoxLimitText = \App\Http\Controllers\Web\Front\Post\ListingAssistant\ListingAssistantController::limitText($aiBoxLimit);
		$aiBoxNoneLeft = $aiBoxOn && $aiBoxLimit->reached();
		$aiBoxServerVoice = $aiBoxOn && $aiBoxService->serverVoiceAvailable($aiBoxUser);
		$aiBoxSpeech = \App\Http\Controllers\Web\Front\Post\ListingAssistant\ListingAssistantController::voiceLanguages();
		$aiBoxStrings = collect([
			'fill', 'filling', 'listening', 'speak', 'rec_pause', 'rec_continue', 'rec_short', 'too_short', 'confirm_replace',
			'restored', 'error', 'done', 'row_category', 'row_no_category', 'row_title', 'row_no_title', 'row_price', 'row_no_price',
			'row_details', 'row_check_details', 'row_city', 'row_city_unknown', 'row_no_city', 'row_tags', 'row_heard',
			'row_photos', 'photo_remove', 'photos_max', 'photo_too_big',
		])->mapWithKeys(fn ($key) => [$key => trans('ai.box_' . $key)])->merge([
			'timeout'     => trans('ai.assistant_timeout'),
			'mic_denied'  => trans('ai.assistant_voice_denied'),
			'mic_missing' => trans('ai.assistant_voice_no_mic'),
			'mic_busy'    => trans('ai.assistant_voice_mic_busy'),
			'no_speech'   => trans('ai.assistant_voice_no_speech'),
		])->all();
		$aiBoxExamples = ['phone', 'car', 'home', 'furniture'];
	@endphp
	<div class="col-12 mb-4 ai-assistant-wrap">
		<div class="ai-assistant card" id="aiAssistant"
		     data-url="{{ url('posts/create/assistant/fill') }}"
		     data-wysiwyg="{{ in_array(config('settings.listing_form.wysiwyg_editor'), ['none', '', null], true) ? '0' : '1' }}"
		     data-selection-url="{{ url('browsing/categories/select') }}"
		     data-max-seconds="{{ (int)config('ai_features.listing_assistant.max_audio_seconds', 60) }}"
		     data-server-voice="{{ $aiBoxServerVoice ? '1' : '0' }}"
		     data-max-photos="{{ \App\Services\ListingAssistant\ListingAssistantService::MAX_PHOTOS }}"
		     data-max-photo-kb="{{ (int)config('settings.upload.max_image_size', 1000) }}"
		     data-speech-lang="{{ $aiBoxSpeech['default'] }}"
		     data-strings="{{ json_encode($aiBoxStrings, JSON_UNESCAPED_UNICODE) }}"
		>
			<div class="card-body">
				<span class="ai-assistant-badge"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ trans('ai.box_badge') }}</span>
				<h2 class="ai-assistant-title">{{ trans('ai.box_title') }}</h2>
				<p class="ai-assistant-hint">{{ trans('ai.box_hint') }}</p>

				@if ($aiBoxLogin)
					<div class="d-flex flex-wrap align-items-center gap-3">
						<a href="#quickLogin" data-bs-toggle="modal" class="btn ai-assistant-fill">
							<i class="fa-solid fa-right-to-bracket"></i> {{ trans('ai.box_login') }}
						</a>
						<span class="small text-secondary">{{ trans('ai.box_login_or') }}</span>
					</div>
				@else
					<div class="ai-assistant-field">
						<label for="aiAssistantText" class="visually-hidden">{{ trans('ai.box_label') }}</label>
						<textarea id="aiAssistantText" class="form-control ai-assistant-text" rows="3"
						          maxlength="{{ (int)config('ai_features.listing_assistant.max_text', 2000) }}"
						          placeholder="{{ trans('ai.assistant_text_placeholder') }}"
						          @disabled($aiBoxNoneLeft)></textarea>
						<button type="button" class="btn ai-assistant-speak d-none" @disabled($aiBoxNoneLeft)
						        title="{{ trans('ai.box_speak') }}" aria-label="{{ trans('ai.box_speak') }}">
							<i class="fa-solid fa-microphone"></i>
						</button>
					</div>

					{{-- Photos: read by the AI, then added to the Photos step --}}
					<div class="ai-assistant-photos">
						<input type="file" class="d-none ai-assistant-photo-input" accept="image/*" multiple tabindex="-1" aria-hidden="true">
						<div class="ai-assistant-thumbs d-none"></div>
						<button type="button" class="btn ai-assistant-photo-add" @disabled($aiBoxNoneLeft)>
							<i class="fa-solid fa-camera"></i> {{ trans('ai.box_photos') }}
						</button>
						<span class="ai-assistant-photo-hint">{{ trans('ai.box_photos_hint', ['max' => \App\Services\ListingAssistant\ListingAssistantService::MAX_PHOTOS]) }}</span>
					</div>

					<div class="ai-assistant-chips">
						<span>{{ trans('ai.box_try') }}</span>
						@foreach ($aiBoxExamples as $example)
							<button type="button" class="ai-assistant-chip" data-example="{{ trans('ai.box_example_' . $example) }}">{{ trans('ai.box_chip_' . $example) }}</button>
						@endforeach
					</div>

					{{-- Voice bar while recording --}}
					<div class="ai-assistant-recording d-none" role="status" aria-live="polite">
						<div class="ai-voice-bar">
							<button type="button" class="ai-voice-cancel ai-assistant-cancel" aria-label="{{ trans('ai.box_rec_delete') }}" title="{{ trans('ai.box_rec_delete') }}">
								<i class="fa-regular fa-trash-can"></i>
							</button>
							<div class="ai-voice-track">
								<span class="ai-voice-dot" aria-hidden="true"></span>
								<span class="ai-assistant-timer">0:00</span>
								<canvas class="ai-voice-wave" aria-hidden="true"></canvas>
							</div>
							<button type="button" class="ai-voice-pause" aria-label="{{ trans('ai.box_rec_pause') }}" title="{{ trans('ai.box_rec_pause') }}">
								<i class="fa-solid fa-pause"></i>
							</button>
							<button type="button" class="ai-voice-send ai-assistant-stop" aria-label="{{ trans('ai.box_rec_send') }}" title="{{ trans('ai.box_rec_send') }}">
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

					<div class="ai-assistant-footer">
						<div class="d-flex flex-wrap align-items-center gap-2">
							@if ($aiBoxNoneLeft)
								<span class="ai-assistant-usage">{{ trans('ai.assistant_limit_reached', ['when' => $aiBoxLimit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL') ?? '']) }}</span>
							@elseif ($aiBoxLimitText)
								<span class="ai-assistant-usage"><i class="fa-solid fa-bolt text-warning"></i> <span class="ai-assistant-usage-text">{{ $aiBoxLimitText }}</span></span>
							@endif
							<button type="button" class="btn btn-link btn-sm ai-assistant-undo d-none">
								<i class="fa-solid fa-rotate-left"></i> {{ trans('ai.box_undo') }}
							</button>
						</div>
						<button type="button" class="btn ai-assistant-fill" @disabled($aiBoxNoneLeft)>
							<i class="fa-solid fa-wand-magic-sparkles"></i> <span class="ai-assistant-fill-label">{{ trans('ai.box_fill') }}</span>
						</button>
					</div>

					<div class="ai-assistant-message small mt-2 d-none" role="status" aria-live="polite"></div>
					<div class="ai-assistant-result mt-3 d-none" aria-live="polite"></div>
					<p class="small text-secondary mt-2 mb-0">{{ trans('ai.box_privacy') }}</p>
				@endif
			</div>
		</div>
	</div>

	@pushOnce('after_styles_stack', 'ai_formbox_css')
		<link href="{{ url('assets/css/ai-formbox.css') }}?v=3" rel="stylesheet">
	@endPushOnce
	@if ($aiBoxOn)
		@pushOnce('after_scripts_stack', 'ai_formbox_js')
			<script src="{{ url('assets/js/app/ai.formbox.js') }}?v=5"></script>
		@endPushOnce
	@endif
@endif
