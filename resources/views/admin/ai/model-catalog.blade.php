{{-- "Load models" on Admin > AI Models (adniro:ai): lists the provider's models and fills the form --}}
<div class="ai-model-catalog"
     data-catalog-url="{{ urlGen()->adminUrl('ai_models/available') }}"
     data-text-loading="{{ trans('ai.catalog_loading') }}"
     data-text-pick="{{ trans('ai.catalog_pick') }}"
     data-text-loaded="{{ trans('ai.catalog_loaded', ['count' => '__COUNT__']) }}"
     data-text-empty="{{ trans('ai.catalog_empty') }}"
     data-text-choose-provider="{{ trans('ai.catalog_choose_provider') }}"
     data-text-unknown-price="{{ trans('ai.catalog_unknown_price') }}"
     data-text-failed="{{ trans('ai.catalog_failed') }}">
	<div class="d-flex flex-wrap align-items-center gap-2">
		<button type="button" class="btn btn-outline-primary btn-sm js-catalog-load">
			<i class="fa-solid fa-list"></i> {{ trans('ai.catalog_load') }}
		</button>
		<select class="form-select form-select-sm js-catalog-select d-none" style="max-width: 420px" aria-label="{{ trans('ai.catalog_pick') }}"></select>
	</div>
	<div class="form-text js-catalog-status" role="status" aria-live="polite">{{ trans('ai.catalog_hint') }}</div>
</div>

{{-- Inline, not pushed: this view is rendered to a string for a custom_html field --}}
<script>
(function () {
	const box = document.querySelector('.ai-model-catalog');
	if (!box) {
		return;
	}
	const form = box.closest('form');
	const button = box.querySelector('.js-catalog-load');
	const select = box.querySelector('.js-catalog-select');
	const status = box.querySelector('.js-catalog-status');
	const text = box.dataset;
	const field = (name) => form ? form.querySelector('[name="' + name + '"]:not([type="hidden"])') : null;
	let models = [];
	let autoLabel = null;
	const autoPrices = {};

	const setStatus = (message, isError) => {
		status.textContent = message;
		status.classList.toggle('text-danger', Boolean(isError));
	};

	const setSwitch = (name, value) => {
		const input = field(name);
		if (input && typeof value === 'boolean') {
			input.checked = value;
		}
	};

	// An unknown price clears only a price the previous pick filled in, never one the admin typed
	const setPrice = (name, value) => {
		const input = field(name);
		if (!input) {
			return;
		}
		if (value !== null && value !== undefined) {
			input.value = value;
			autoPrices[name] = input.value;
		} else if (autoPrices[name] !== undefined && input.value === autoPrices[name]) {
			input.value = '';
			delete autoPrices[name];
		}
	};

	const apply = (model) => {
		const modelId = field('model_id');
		const label = field('label');
		if (modelId) {
			modelId.value = model.id;
		}
		if (label && (label.value === '' || label.value === autoLabel)) {
			label.value = model.label;
			autoLabel = model.label;
		}
		setSwitch('supports_vision', model.vision);
		setSwitch('supports_structured_output', model.structured);
		setSwitch('supports_transcription', model.transcription);
		setPrice('input_price_per_mtok', model.input_price);
		setPrice('output_price_per_mtok', model.output_price);
		setStatus(model.input_price === null ? text.textUnknownPrice : '', false);
	};

	const fill = (list) => {
		models = list;
		select.replaceChildren(new Option(text.textPick, ''));
		const current = field('model_id') ? field('model_id').value : '';
		list.forEach((model, index) => {
			const caption = model.label && model.label !== model.id ? model.label + ' (' + model.id + ')' : model.id;
			select.add(new Option(caption, String(index), false, model.id === current));
		});
		select.classList.toggle('d-none', list.length === 0);
	};

	button.addEventListener('click', async () => {
		const provider = field('ai_provider_id') || (form && form.querySelector('[name="ai_provider_id"]'));
		if (!provider || !provider.value) {
			setStatus(text.textChooseProvider, true);
			return;
		}
		button.disabled = true;
		setStatus(text.textLoading, false);
		try {
			const response = await fetch(text.catalogUrl + '?provider=' + encodeURIComponent(provider.value), {
				headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
				credentials: 'same-origin',
			});
			const body = await response.json().catch(() => null);
			if (!response.ok || !body || !body.success) {
				fill([]);
				setStatus(body && body.error ? body.error : text.textFailed + ' HTTP ' + response.status, true);
				return;
			}
			fill(body.data);
			setStatus(body.data.length ? text.textLoaded.replace('__COUNT__', body.data.length) : text.textEmpty, body.data.length === 0);
		} catch (error) {
			fill([]);
			setStatus(text.textFailed + ' ' + error.message, true);
		} finally {
			button.disabled = false;
		}
	});

	select.addEventListener('change', () => {
		const model = models[Number(select.value)];
		if (select.value !== '' && model) {
			apply(model);
		}
	});
})();
</script>
