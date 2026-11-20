{{-- Quick post composer form (adniro:ai) --}}
					<div class="border rounded bg-body-tertiary p-4 mb-4" id="quickPost">
	<h1 class="h3 fw-bold mb-1"><i class="bi bi-stars"></i> {{ trans('ai.qp_title') }}</h1>
	<p class="text-muted mb-4">{{ trans('ai.qp_intro') }}</p>
	
	<form id="quickPostForm" novalidate>
		{{-- Photos --}}
		@php
			$photos ??= [];
			$photoLimit ??= 5;
		@endphp
		<div class="mb-3">
			<div class="d-flex flex-wrap gap-2" id="qpPhotos" data-limit="{{ $photoLimit }}">
				@foreach ($photos as $photo)
					<div class="qp-photo position-relative" data-key="{{ $photo['key'] }}" style="width: 88px; height: 88px;">
						<img src="{{ $photo['url'] }}" alt="" class="rounded border w-100 h-100" style="object-fit: cover;">
						<button type="button" class="btn btn-sm btn-light rounded-circle position-absolute top-0 end-0 m-1 p-0 qp-photo-remove" style="width: 24px; height: 24px; line-height: 1;" aria-label="{{ trans('ai.qp_photo_remove') }}">&times;</button>
					</div>
				@endforeach
				<label for="qpPhotoInput" id="qpPhotoTile"
				       class="d-flex flex-column align-items-center justify-content-center rounded border border-2 text-center text-primary bg-body {{ count($photos) >= $photoLimit ? 'd-none' : '' }}"
				       style="width: 88px; height: 88px; border-style: dashed !important; cursor: pointer;">
					<i class="bi bi-camera fs-4"></i>
					<span class="small lh-sm">{{ trans('ai.qp_photo_add') }}</span>
				</label>
			</div>
			<input type="file" id="qpPhotoInput" class="d-none" accept="image/*" multiple>
			<div class="form-text">{{ trans('ai.qp_photo_hint', ['limit' => $photoLimit]) }}</div>
		</div>
		
		<label for="qpText" class="form-label fw-bold">{{ trans('ai.qp_text_label') }}</label>
		@php
			$voice ??= ['default' => app()->getLocale(), 'languages' => []];
		@endphp
		<div class="position-relative mb-2">
			<textarea id="qpText"
			          name="text"
			          class="form-control form-control-lg"
			          style="padding-bottom: 3.25rem;"
			          rows="5"
			          maxlength="{{ $maxText }}"
			          placeholder="{{ trans('ai.qp_text_placeholder') }}"
			          autofocus
			>{{ $redraftText ?? '' }}</textarea>
			{{-- Voice: shown by the script only where the browser has speech recognition --}}
			<div class="position-absolute bottom-0 end-0 m-2 d-none align-items-center gap-1" id="qpVoice"
			     data-default="{{ $voice['default'] }}"
			     data-languages="{{ json_encode($voice['languages']) }}"
			     data-server="{{ !empty($voice['server']) ? '1' : '0' }}"
			     data-server-url="{{ $voice['serverUrl'] ?? '' }}"
			     data-max-seconds="{{ (int)($voice['maxSeconds'] ?? 60) }}">
				<select id="qpVoiceLang" class="form-select form-select-sm w-auto" aria-label="{{ trans('ai.qp_voice_language') }}">
					@foreach ($voice['languages'] as $language)
						<option value="{{ $language['tag'] }}" @selected($language['tag'] === $voice['default'])>{{ $language['label'] }}</option>
					@endforeach
				</select>
				<button type="button" class="btn btn-outline-primary rounded-circle" id="qpMic"
				        style="width: 42px; height: 42px;" aria-pressed="false"
				        aria-label="{{ trans('ai.qp_voice_start') }}"
				        data-label-start="{{ trans('ai.qp_voice_start') }}"
				        data-label-stop="{{ trans('ai.qp_voice_stop') }}">
					<i class="bi bi-mic-fill"></i>
				</button>
			</div>
		</div>
		<div class="small text-muted mb-2 d-none" id="qpVoiceStatus" aria-live="polite"></div>
		<div class="small text-muted mb-2 d-none" id="qpVoiceHint">{{ trans('ai.qp_voice_hint') }}</div>
		
		<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
			<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="qpLocation">
				<i class="bi bi-geo-alt"></i> <span>{{ trans('ai.qp_use_location') }}</span>
			</button>
			<span class="small text-muted ms-auto" id="qpCounter">0 / {{ $maxText }}</span>
		</div>
		
		<div class="alert alert-warning d-none" id="qpMessage" role="alert"></div>
		
		<div class="d-grid mb-3">
			<button type="submit" class="btn btn-primary btn-lg" id="qpSubmit" disabled>
				<i class="bi bi-magic"></i> {{ trans('ai.qp_submit') }}
			</button>
		</div>
		
		<div class="d-flex flex-wrap justify-content-between gap-2 small">
			<span class="text-muted" id="qpLimit">
				@if ($limit->reached())
					{{ trans('ai.qp_limit_reached', ['when' => $limit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL')]) }}
				@elseif (!empty($limitText))
					{{ $limitText }}
				@endif
			</span>
			<a href="{{ $formUrl }}" class="fw-bold">{{ trans('ai.qp_fill_myself') }}</a>
		</div>
	</form>
	
	<p class="small text-muted mt-4 mb-0">{{ trans('ai.qp_privacy') }}</p>
	<p class="small text-muted mt-1 mb-0 d-none" id="qpVoicePrivacy">{{ trans('ai.qp_voice_privacy') }}</p>
	<p class="small text-muted mt-1 mb-0 d-none" id="qpVoiceServerPrivacy">{{ trans('ai.qp_voice_server_privacy') }}</p>
</div>
