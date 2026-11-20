{{-- adniro:ai - on the step 1 form after a quick post draft: banner and field highlights --}}
@php
	$qpMarks = session(\App\Services\QuickPost\DraftToPostInput::MARKS_KEY);
	$qpInput = (array)session('postInput', []);
	$qpShow = is_array($qpMarks) && !empty($qpMarks['draft']) && !empty($qpInput);
	$qpFlags = $qpShow ? collect((array)($qpMarks['policy_flags'] ?? []))
		->filter(fn ($code) => \App\Services\Ai\Moderation\Policy::severity($code) !== null)
		->map(fn ($code) => \App\Services\Ai\Moderation\Policy::label($code)) : collect();
@endphp
@if ($qpShow)
	<div class="col-12">
		<div class="alert alert-info d-flex flex-wrap align-items-start gap-2" role="status" id="qpBanner">
			<i class="bi bi-stars fs-5"></i>
			<div class="flex-grow-1">
				<p class="fw-bold mb-1">{{ trans('ai.qp_banner_title') }}</p>
				<p class="mb-1 small">{{ trans('ai.qp_banner_text') }}</p>
				@if ((int)($qpMarks['items_detected'] ?? 1) > 1)
					<p class="mb-1 small">{{ trans('ai.qp_banner_items') }}</p>
				@endif
				@if ($qpFlags->isNotEmpty())
					<p class="mb-1 small text-danger">{{ trans('ai.qp_banner_flags', ['categories' => $qpFlags->implode(', ')]) }}</p>
				@endif
				<a class="small" href="{{ url('posts/create/quick') . '?redraft=' . urlencode($qpMarks['draft']) }}">{{ trans('ai.qp_banner_redo') }}</a>
			</div>
		</div>
	</div>
	
	@push('after_scripts_stack')
		<script>
			// The form only restores the chosen category (and loads its custom fields) after a
			// validation error; a draft's category is shown the same way, once the page is ready
			@if (!empty($qpInput['category_id']))
				window.addEventListener('load', function () {
					if (typeof getCategories === 'function' && typeof categoryWasSelected !== 'undefined' && !categoryWasSelected) {
						categoryWasSelected = true;
						getCategories(siteUrl, languageCode);
					}
				});
			@endif
			
			(function () {
				const marks = @json((array)($qpMarks['fields'] ?? []));
				const labels = {check: @json(trans('ai.qp_mark_check')), missing: @json(trans('ai.qp_mark_missing'))};
				const targets = {
					category_id: () => document.getElementById('catsContainer'),
					city_id: () => document.getElementById('cityBox') || document.getElementById('cityId'),
					post_type_id: () => document.getElementById('postTypeBloc'),
					price: () => document.getElementById('priceBloc') || scope().querySelector('[name="price"]'),
				};
				// Only inside the listing form: the page head also has a <meta name="description">
				const scope = () => document.getElementById('payableForm') || document.body;
				const find = (key) => {
					if (targets[key]) {
						return targets[key]();
					}
					if (key.startsWith('cf.')) {
						const id = key.slice(3);
						return scope().querySelector('[name="cf[' + id + ']"], [name^="cf[' + id + ']["]');
					}
					return scope().querySelector('[name="' + key + '"]');
				};
				const done = new Set();
				let firstMissing = null;
				const apply = () => {
					Object.entries(marks).forEach(([key, mark]) => {
						if (done.has(key)) {
							return;
						}
						const el = find(key);
						if (!el) {
							return;
						}
						const box = el.closest('.mb-3') || el.parentElement;
						if (!box) {
							return;
						}
						done.add(key);
						box.classList.add('rounded', 'p-2', mark === 'missing' ? 'border-danger' : 'border-warning');
						box.style.border = '2px solid';
						const badge = document.createElement('span');
						badge.className = 'badge ms-2 ' + (mark === 'missing' ? 'text-bg-danger' : 'text-bg-warning');
						badge.textContent = labels[mark] || mark;
						const label = box.querySelector('label');
						(label || box).appendChild(badge);
						if (mark === 'missing' && !firstMissing) {
							firstMissing = el;
						}
					});
				};
				apply();
				// Custom fields arrive after the category loads them
				const observer = new MutationObserver(apply);
				observer.observe(document.body, {childList: true, subtree: true});
				setTimeout(() => observer.disconnect(), 15000);
				if (firstMissing && typeof firstMissing.focus === 'function' && firstMissing.tagName !== 'DIV') {
					firstMissing.focus({preventScroll: false});
				}
			})();
		</script>
	@endpush
@endif
