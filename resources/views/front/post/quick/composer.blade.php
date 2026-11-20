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
			const maxText = {{ $maxText }};
			const limitReached = {{ $limit->reached() ? 'true' : 'false' }};
			const steps = @json([trans('ai.qp_progress_1'), trans('ai.qp_progress_2'), trans('ai.qp_progress_3')]);
			let position = null;
			let lastDraft = @json($redraftToken ?? null);
			
			const refresh = () => {
				const length = text.value.trim().length;
				counter.textContent = text.value.length + ' / ' + maxText;
				submit.disabled = limitReached || length < minText;
			};
			text.addEventListener('input', refresh);
			refresh();
			
			const showMessage = (msg) => {
				message.textContent = msg || '';
				message.classList.toggle('d-none', !msg);
			};
			
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
