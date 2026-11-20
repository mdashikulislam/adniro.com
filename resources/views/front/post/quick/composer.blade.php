{{-- Quick post composer (adniro:ai) --}}
@extends('front.layouts.master')

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-7 col-md-9 col-12">
					@include('front.post.partials.notification')
					
					@include('front.post.quick.partials.form')
				</div>
			</div>
		</div>
	</div>
	
	@include('front.post.quick.partials.overlay')
@endsection

@section('after_scripts')
	@parent
	@php
		$voiceMessages = [
			'listening'   => trans('ai.qp_voice_listening'),
			'denied'      => trans('ai.qp_voice_denied'),
			'no_speech'   => trans('ai.qp_voice_no_speech'),
			'unavailable' => trans('ai.qp_voice_unavailable'),
			'time_up'     => trans('ai.qp_voice_time_up'),
		];
	@endphp
	<script>
		(function () {
			const form = document.getElementById('quickPostForm');
			const text = document.getElementById('qpText');
			const submit = document.getElementById('qpSubmit');
			const counter = document.getElementById('qpCounter');
			const message = document.getElementById('qpMessage');
			const limitEl = document.getElementById('qpLimit');
			const overlay = document.getElementById('qpOverlay');
			const progress = document.getElementById('qpProgress');
			const locationBtn = document.getElementById('qpLocation');
			const minText = {{ $minText }};
			const photosBox = document.getElementById('qpPhotos');
			const photoTile = document.getElementById('qpPhotoTile');
			const photoInput = document.getElementById('qpPhotoInput');
			const photoLimit = parseInt(photosBox.dataset.limit, 10) || 5;
			const photoMaxKb = {{ (int)$photoMaxKb }};
			const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
			let uploading = 0;
			const maxText = {{ $maxText }};
			const limitReached = {{ $limit->reached() ? 'true' : 'false' }};
			const steps = @json([trans('ai.qp_progress_1'), trans('ai.qp_progress_2'), trans('ai.qp_progress_3')]);
			let position = null;
			let lastDraft = @json($redraftToken ?? null);
			
			const photoCount = () => photosBox.querySelectorAll('.qp-photo:not(.qp-uploading)').length;
			const refresh = () => {
				const length = text.value.trim().length;
				counter.textContent = text.value.length + ' / ' + maxText;
				submit.disabled = limitReached || uploading > 0 || (length < minText && photoCount() === 0);
				photoTile.classList.toggle('d-none', photosBox.querySelectorAll('.qp-photo').length >= photoLimit);
			};
			text.addEventListener('input', refresh);
			refresh();
			
			const showMessage = (msg) => {
				message.textContent = msg || '';
				message.classList.toggle('d-none', !msg);
			};
			
			/* Photos: shrunk to 1600 px JPEG in the browser (which also converts HEIC where the
			   browser can read it), then uploaded one per request into the photos step's storage */
			const shrink = (file) => new Promise((resolve) => {
				const url = URL.createObjectURL(file);
				const img = new Image();
				img.onload = () => {
					const scale = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight));
					const canvas = document.createElement('canvas');
					canvas.width = Math.round(img.naturalWidth * scale);
					canvas.height = Math.round(img.naturalHeight * scale);
					canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
					URL.revokeObjectURL(url);
					canvas.toBlob((blob) => resolve(blob ? new File([blob], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg', {type: 'image/jpeg'}) : file), 'image/jpeg', 0.85);
				};
				/* An image the browser cannot read is sent as it is: the server decides */
				img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
				img.src = url;
			});
			
			const addTile = (url, key) => {
				const tile = document.createElement('div');
				tile.className = 'qp-photo position-relative';
				tile.style.cssText = 'width: 88px; height: 88px;';
				if (key !== null) {
					tile.dataset.key = key;
				}
				const img = document.createElement('img');
				img.alt = '';
				img.className = 'rounded border w-100 h-100';
				img.style.objectFit = 'cover';
				img.src = url;
				const remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'btn btn-sm btn-light rounded-circle position-absolute top-0 end-0 m-1 p-0 qp-photo-remove';
				remove.style.cssText = 'width: 24px; height: 24px; line-height: 1;';
				remove.setAttribute('aria-label', @json(trans('ai.qp_photo_remove')));
				remove.textContent = '\u00d7';
				tile.append(img, remove);
				photosBox.insertBefore(tile, photoTile);
				return tile;
			};
			
			photoInput.addEventListener('change', async () => {
				const files = Array.from(photoInput.files || []);
				photoInput.value = '';
				showMessage('');
				for (const original of files) {
					if (photosBox.querySelectorAll('.qp-photo').length >= photoLimit) {
						showMessage(@json(trans('ai.qp_photo_limit', ['limit' => $photoLimit])));
						break;
					}
					const tile = addTile(URL.createObjectURL(original), null);
					tile.classList.add('qp-uploading', 'opacity-50');
					uploading++;
					refresh();
					try {
						const file = await shrink(original);
						if (photoMaxKb > 0 && file.size > photoMaxKb * 1024) {
							throw new Error(@json(trans('ai.qp_photo_too_big')));
						}
						const body = new FormData();
						body.append('photo', file);
						const response = await fetch(@json($photoUrl), {
							method: 'POST',
							headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest'},
							body,
						});
						const data = await response.json().catch(() => ({}));
						if (!response.ok || !data.ok) {
							throw new Error(data.message || @json(trans('ai.qp_photo_failed')));
						}
						tile.dataset.key = data.photo.key;
						if (data.photo.url) {
							tile.querySelector('img').src = data.photo.url;
						}
						tile.classList.remove('qp-uploading', 'opacity-50');
					} catch (e) {
						tile.remove();
						showMessage(e.message);
					} finally {
						uploading--;
						refresh();
					}
				}
			});
			
			/* Removing a photo uses the photos step's own delete action */
			photosBox.addEventListener('click', async (event) => {
				const button = event.target.closest('.qp-photo-remove');
				if (!button) {
					return;
				}
				const tile = button.closest('.qp-photo');
				if (!tile.dataset.key) {
					return;
				}
				button.disabled = true;
				try {
					const response = await fetch(@json(url('posts/create/photos')) + '/' + encodeURIComponent(tile.dataset.key) + '/delete', {
						method: 'POST',
						headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest'},
					});
					const data = await response.json().catch(() => ({}));
					if (data.status == 1) {
						tile.remove();
					} else {
						button.disabled = false;
						showMessage(data.message || @json(trans('ai.qp_photo_failed')));
					}
				} catch (e) {
					button.disabled = false;
				}
				refresh();
			});
			
			/* Voice: the browser's speech recognition writes into the text box, live and editable.
			   Where the browser has none, the mic is hidden and a hint points to the keyboard's
			   dictation key. The recognizer is looked up when the mic is tapped. */
			const voiceBox = document.getElementById('qpVoice');
			const mic = document.getElementById('qpMic');
			const voiceLang = document.getElementById('qpVoiceLang');
			const voiceStatus = document.getElementById('qpVoiceStatus');
			const Recognizer = () => window.SpeechRecognition || window.webkitSpeechRecognition || null;
			const voiceSupported = !!Recognizer() && window.isSecureContext !== false;
			const voiceMessages = @json($voiceMessages);
			const VOICE_MAX_MS = 60000;
			let recognition = null;
			let voiceTimer = null;
			let voiceBase = '';
			let voiceFinal = '';
			
			const setVoiceStatus = (msg) => {
				voiceStatus.textContent = msg || '';
				voiceStatus.classList.toggle('d-none', !msg);
			};
			const setListening = (on) => {
				mic.classList.toggle('btn-danger', on);
				mic.classList.toggle('btn-outline-primary', !on);
				mic.setAttribute('aria-pressed', on ? 'true' : 'false');
				mic.setAttribute('aria-label', on ? mic.dataset.labelStop : mic.dataset.labelStart);
				mic.querySelector('i').className = on ? 'bi bi-stop-fill' : 'bi bi-mic-fill';
				voiceLang.disabled = on;
			};
			const stopVoice = () => {
				clearTimeout(voiceTimer);
				if (recognition) {
					try { recognition.stop(); } catch (e) {}
				}
			};
			const joinText = (a, b) => (a && b && !/\s$/.test(a) ? a + ' ' + b : a + b);
			
			if (voiceSupported) {
				voiceBox.classList.replace('d-none', 'd-flex');
				document.getElementById('qpVoicePrivacy').classList.remove('d-none');
				try {
					const saved = window.localStorage.getItem('qpVoiceLang');
					if (saved && Array.from(voiceLang.options).some((o) => o.value === saved)) {
						voiceLang.value = saved;
					}
				} catch (e) {}
				voiceLang.addEventListener('change', () => {
					try { window.localStorage.setItem('qpVoiceLang', voiceLang.value); } catch (e) {}
				});
			} else {
				document.getElementById('qpVoiceHint').classList.remove('d-none');
			}
			
			mic.addEventListener('click', () => {
				if (recognition) {
					stopVoice();
					return;
				}
				const SR = Recognizer();
				if (!SR) {
					return;
				}
				recognition = new SR();
				recognition.lang = voiceLang.value || voiceBox.dataset.default;
				recognition.continuous = true;
				recognition.interimResults = true;
				voiceBase = text.value;
				voiceFinal = '';
				
				recognition.onstart = () => {
					setListening(true);
					setVoiceStatus(voiceMessages.listening);
					voiceTimer = setTimeout(() => {
						setVoiceStatus(voiceMessages.time_up);
						stopVoice();
					}, VOICE_MAX_MS);
				};
				recognition.onresult = (event) => {
					let interim = '';
					for (let i = event.resultIndex; i < event.results.length; i++) {
						const piece = event.results[i][0].transcript;
						if (event.results[i].isFinal) {
							voiceFinal = joinText(voiceFinal, piece.trim());
						} else {
							interim += piece;
						}
					}
					text.value = joinText(voiceBase, joinText(voiceFinal, interim.trim())).slice(0, maxText);
					refresh();
				};
				recognition.onerror = (event) => {
					const map = {'not-allowed': 'denied', 'service-not-allowed': 'denied', 'no-speech': 'no_speech'};
					setVoiceStatus(voiceMessages[map[event.error] || 'unavailable']);
				};
				recognition.onend = () => {
					clearTimeout(voiceTimer);
					recognition = null;
					setListening(false);
					if (voiceStatus.textContent === voiceMessages.listening) {
						setVoiceStatus('');
					}
					text.value = joinText(voiceBase, voiceFinal).slice(0, maxText);
					refresh();
					text.focus();
				};
				try {
					recognition.start();
				} catch (e) {
					recognition = null;
					setVoiceStatus(voiceMessages.unavailable);
				}
			});
			
			/* Stop listening before the draft is sent */
			form.addEventListener('submit', stopVoice, true);
			
			if (!('geolocation' in navigator)) {
				locationBtn.classList.add('d-none');
			}
			locationBtn.addEventListener('click', () => {
				locationBtn.disabled = true;
				navigator.geolocation.getCurrentPosition((pos) => {
					position = {lat: pos.coords.latitude, lon: pos.coords.longitude};
					locationBtn.classList.replace('btn-outline-secondary', 'btn-success');
					locationBtn.querySelector('span').textContent = @json(trans('ai.qp_location_set'));
				}, () => {
					locationBtn.disabled = false;
					locationBtn.querySelector('span').textContent = @json(trans('ai.qp_location_denied'));
				}, {timeout: 8000, maximumAge: 600000});
			});
			
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				if (submit.disabled) {
					return;
				}
				showMessage('');
				overlay.classList.replace('d-none', 'd-flex');
				let step = 0;
				const ticker = setInterval(() => {
					step = Math.min(step + 1, steps.length - 1);
					progress.textContent = steps[step];
				}, 6000);
				const controller = new AbortController();
				const timer = setTimeout(() => controller.abort(), {{ $timeoutMs }});
				
				const body = {text: text.value};
				if (position) {
					body.lat = position.lat;
					body.lon = position.lon;
				}
				if (lastDraft) {
					body.redraft_of = lastDraft;
				}
				
				try {
					const response = await fetch(@json($draftUrl), {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'Accept': 'application/json',
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
							'X-Requested-With': 'XMLHttpRequest',
						},
						body: JSON.stringify(body),
						signal: controller.signal,
					});
					const data = await response.json().catch(() => ({}));
					if (data.limit_text) {
						limitEl.textContent = data.limit_text;
					}
					if (data.redirect) {
						window.location.href = data.redirect;
						return;
					}
					if (data.reason === 'limit') {
						submit.disabled = true;
						limitEl.textContent = data.message || '';
					}
					showMessage(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : @json(trans('ai.qp_unavailable'))));
				} catch (e) {
					showMessage(@json(trans('ai.qp_timeout')));
				} finally {
					clearTimeout(timer);
					clearInterval(ticker);
					overlay.classList.replace('d-flex', 'd-none');
					progress.textContent = steps[0];
				}
			});
		})();
	</script>
@endsection
