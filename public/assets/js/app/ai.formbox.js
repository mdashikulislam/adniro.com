/*
 * The AI box in the listing form (adniro:ai), ported from classifedz.com's ai.quickstart.js.
 * The seller describes the item once: typed, recorded (sent to the server when a speech-to-text model
 * is assigned) or dictated with the browser's own SpeechRecognition, optionally with up to four photos
 * (chosen, or taken with the camera: the phone's camera app, or a camera window on computers)
 * (shrunk in the browser, read by the AI, then added to the form's Photos step by the server). posts/create/assistant/fill returns
 * the form's fields; this script fills the form the way a person would (the form's own category and
 * city functions, input/change events), highlights what changed, lists what still needs the seller,
 * and can undo it all. The seller then continues as usual.
 */
(function () {
	'use strict';

	var box = document.getElementById('aiAssistant');
	if (!box) {
		return;
	}
	var form = box.closest('form');
	var textEl = box.querySelector('.ai-assistant-text');
	var fillButton = box.querySelector('.ai-assistant-fill');
	if (!form || !textEl || !fillButton) {
		return;
	}

	var fillLabel = box.querySelector('.ai-assistant-fill-label');
	var speakButton = box.querySelector('.ai-assistant-speak');
	var undoButton = box.querySelector('.ai-assistant-undo');
	var usageEl = box.querySelector('.ai-assistant-usage');
	var messageEl = box.querySelector('.ai-assistant-message');
	var resultEl = box.querySelector('.ai-assistant-result');
	var recordingEl = box.querySelector('.ai-assistant-recording');
	var timerEl = box.querySelector('.ai-assistant-timer');
	var stopButton = box.querySelector('.ai-assistant-stop');
	var cancelButton = box.querySelector('.ai-assistant-cancel');
	var usageTextEl = box.querySelector('.ai-assistant-usage-text');
	var isWysiwyg = box.dataset.wysiwyg === '1';
	var photoInput = box.querySelector('.ai-assistant-photo-input');
	var photoAddButton = box.querySelector('.ai-assistant-photo-add');
	var thumbsEl = box.querySelector('.ai-assistant-thumbs');
	var maxPhotos = parseInt(box.dataset.maxPhotos, 10) || 4;
	var maxPhotoBytes = (parseInt(box.dataset.maxPhotoKb, 10) || 1000) * 1024;
	var photos = []; // [{file: File, url: string}]
	var maxSeconds = parseInt(box.dataset.maxSeconds, 10) || 60;
	var busy = false;
	var snapshot = null;
	var lastDraft = null; // re-filling the same item is free a couple of times
	var strings = {};
	try {
		strings = JSON.parse(box.dataset.strings || '{}');
	} catch (e) {
	}

	// ---------- helpers ----------

	// A translated text; :name placeholders get the escaped value in bold
	function t(key, params) {
		var text = escapeHtml(strings[key] || key);
		Object.keys(params || {}).forEach(function (name) {
			text = text.split(':' + name).join('<strong>' + escapeHtml(params[name]) + '</strong>');
		});
		return text;
	}

	function plain(key) {
		return strings[key] || key;
	}

	function q(selector) {
		return form.querySelector(selector);
	}

	function escapeHtml(value) {
		var div = document.createElement('div');
		div.textContent = value == null ? '' : String(value);
		return div.innerHTML;
	}

	function fire(el) {
		if (!el) {
			return;
		}
		el.dispatchEvent(new Event('input', {bubbles: true}));
		el.dispatchEvent(new Event('change', {bubbles: true}));
		if (window.jQuery) {
			window.jQuery(el).trigger('change.select2');
		}
	}

	function highlight(el) {
		if (!el) {
			return;
		}
		var wrap = el.closest('.mb-3, .col-12, .col-md-8, .col-md-12') || el;
		wrap.classList.remove('ai-filled');
		void wrap.offsetWidth;
		wrap.classList.add('ai-filled');
	}

	function setMessage(text, type) {
		messageEl.textContent = text || '';
		messageEl.className = 'ai-assistant-message small mt-2 text-' + (type || 'secondary') + (text ? '' : ' d-none');
	}

	// Browser error names (getUserMedia and SpeechRecognition) mapped to a message. Only a refused
	// permission is "blocked"; a missing microphone or one another app holds needs different advice.
	var MIC_ERRORS = {
		NotAllowedError: 'mic_denied',
		PermissionDeniedError: 'mic_denied',
		SecurityError: 'mic_denied',
		'not-allowed': 'mic_denied',
		'service-not-allowed': 'mic_denied',
		NotFoundError: 'mic_missing',
		DevicesNotFoundError: 'mic_missing',
		OverconstrainedError: 'mic_missing',
		'audio-capture': 'mic_missing'
	};

	// A site cannot switch a microphone or camera on: only the person can, in the browser or the system.
	// So when one is refused, missing or busy, a popup shows the steps for that case, then offers a way on.
	var IS_MAC = /Mac/i.test((navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || '');
	var HELP_STEPS = {
		mic_denied: ['mic_denied_step1', 'mic_denied_step2', IS_MAC ? 'mic_denied_step_mac' : null],
		mic_missing: ['mic_missing_step1', IS_MAC ? 'mic_missing_step_mac' : null, 'mic_missing_step2'],
		mic_busy: ['mic_busy_step1', 'mic_busy_step2'],
		camera_denied: ['mic_denied_step1', 'camera_denied_step2', IS_MAC ? 'camera_denied_step_mac' : null],
		camera_missing: ['camera_missing_step1', 'camera_missing_step2'],
		camera_busy: ['mic_busy_step1', 'mic_busy_step2']
	};

	/**
	 * @param {string} key  mic_denied, camera_busy, ...
	 * @param {{icon: string, retry: function, otherLabel: string, other: function}} options
	 */
	function showDeviceHelp(key, options) {
		var steps = (HELP_STEPS[key] || []).filter(Boolean);
		if (!steps.length || typeof window.Swal === 'undefined') {
			return; // the message under the box already says what happened
		}
		var list = steps.map(function (step) {
			return '<li>' + t(step) + '</li>';
		}).join('');
		window.Swal.fire({
			title: plain(key + '_title'),
			html: '<ol class="ai-mic-help">' + list + '</ol>',
			icon: /_busy$/.test(key) ? 'info' : 'warning',
			showCancelButton: true,
			confirmButtonText: '<i class="fa-solid ' + options.icon + '"></i> ' + escapeHtml(plain('mic_try_again')),
			cancelButtonText: escapeHtml(options.otherLabel),
			theme: (typeof window.isDarkThemeEnabled === 'function' && window.isDarkThemeEnabled()) ? 'dark' : 'light'
		}).then(function (result) {
			if (result.isConfirmed) {
				options.retry();
			} else if (result.dismiss === window.Swal.DismissReason.cancel) {
				options.other();
			}
		});
	}

	function showMicHelp(key) {
		showDeviceHelp(key, {
			icon: 'fa-microphone',
			retry: function () {
				speakButton.click();
			},
			otherLabel: plain('mic_type_instead'),
			other: function () {
				textEl.focus();
			}
		});
	}

	function showCameraHelp(key) {
		showDeviceHelp(key, {
			icon: 'fa-camera',
			retry: function () {
				cameraButton.click();
			},
			otherLabel: plain('camera_choose'),
			other: function () {
				photoInput.click();
			}
		});
	}

	function setBusy(state, label) {
		busy = state;
		fillButton.disabled = state;
		textEl.disabled = state;
		if (speakButton) {
			speakButton.disabled = state;
		}
		if (photoAddButton) {
			photoAddButton.disabled = state;
		}
		if (cameraButton) {
			cameraButton.disabled = state;
		}
		fillLabel.textContent = label || plain('fill');
	}

	function updateUsage(text) {
		if (text && usageTextEl) {
			usageTextEl.textContent = text;
		}
	}

	function tagsEl() {
		return document.getElementById('tags') || q('select[name="tags[]"]');
	}

	// ---------- description editor (TinyMCE, Summernote or textarea) ----------

	function descriptionEl() {
		return q('textarea[name="description"]');
	}

	function editor() {
		var el = descriptionEl();
		return (el && window.tinymce && el.id) ? window.tinymce.get(el.id) : null;
	}

	function getDescription() {
		var ed = editor();
		var el = descriptionEl();
		if (ed) {
			return ed.getContent();
		}
		if (el && window.jQuery && window.jQuery(el).data('summernote')) {
			return window.jQuery(el).summernote('code');
		}
		return el ? el.value : '';
	}

	function setDescription(html, text) {
		var ed = editor();
		var el = descriptionEl();
		var value = isWysiwyg ? html : text;
		if (ed) {
			ed.setContent(value);
			ed.save();
		} else if (el && window.jQuery && window.jQuery(el).data('summernote')) {
			window.jQuery(el).summernote('code', value);
		} else if (el) {
			el.value = value;
		}
		fire(el);
		highlight(el);
	}

	// ---------- fields ----------

	function setValue(el, value) {
		if (!el) {
			return;
		}
		if (el.type === 'checkbox' || el.type === 'radio') {
			el.checked = !!value;
		} else {
			el.value = value;
		}
		fire(el);
		highlight(el);
	}

	function selectCategory(category) {
		if (typeof window.appendSelectedCategory !== 'function' || !category) {
			return false;
		}
		var label = (typeof window.editLabel !== 'undefined') ? window.editLabel : '';
		var parentUrl = box.dataset.selectionUrl + (category.parent_id ? ('?parentId=' + encodeURIComponent(category.parent_id)) : '');
		var outputHtml = escapeHtml(category.name)
			+ ' [ <a href="#browseCategories" data-bs-toggle="modal"'
			+ ' class="modal-cat-link open-selection-url link-primary text-decoration-none"'
			+ ' data-selection-url="' + escapeHtml(parentUrl) + '">'
			+ '<i class="fa-regular fa-pen-to-square"></i> ' + escapeHtml(label) + '</a> ]';
		window.appendSelectedCategory(window.siteUrl, window.languageCode, category.id, category.type || 'classified', outputHtml, true);
		highlight(document.getElementById('catsContainer'));
		return true;
	}

	// Wait until the category's detail fields have loaded into #cfContainer
	function whenFieldsLoaded(callback) {
		var container = document.getElementById('cfContainer');
		if (!container) {
			return;
		}
		var done = false;
		var finish = function () {
			if (done) {
				return;
			}
			done = true;
			observer.disconnect();
			setTimeout(callback, 150);
		};
		var observer = new MutationObserver(function () {
			if (container.querySelector('[name^="cf["]') && !container.querySelector('.spinner-border')) {
				finish();
			}
		});
		observer.observe(container, {childList: true, subtree: true});
		setTimeout(function () {
			if (!done) {
				done = true;
				observer.disconnect();
			}
		}, 15000);
	}

	function fillDetails(cf) {
		Object.keys(cf || {}).forEach(function (fieldId) {
			var value = cf[fieldId];
			var name = 'cf[' + fieldId + ']';
			if (Array.isArray(value)) {
				value.forEach(function (optionId) {
					setValue(q('input[name="' + name + '[' + optionId + ']"]'), true);
				});
				return;
			}
			var radio = q('input[type="radio"][name="' + name + '"][value="' + String(value).replace(/"/g, '') + '"]');
			if (radio) {
				setValue(radio, true);
				return;
			}
			var el = q('[name="' + name + '"]:not([type="hidden"])') || q('[name="' + name + '"]');
			if (!el) {
				return;
			}
			if (el.type === 'checkbox') {
				setValue(el, String(value) === '1');
			} else {
				setValue(el, value);
			}
		});
	}

	function setCity(city) {
		if (!city) {
			return false;
		}
		var cityEl = document.getElementById('cityId');
		// Location pop-up mode: use its own selectCity() with a stand-in link
		if (typeof window.selectCity === 'function' && document.getElementById('selectedCityId')) {
			var link = document.createElement('a');
			link.setAttribute('data-id', city.id);
			link.setAttribute('data-name', city.name);
			link.setAttribute('data-admin-type', city.admin_type || '0');
			link.setAttribute('data-admin-code', city.admin_code || '0');
			window.selectCity(link);
		} else if (cityEl && window.jQuery) {
			window.jQuery(cityEl).empty().append(new Option(city.name, city.id, true, true)).trigger('change');
		} else {
			return false;
		}
		highlight(cityEl);
		return true;
	}

	function setTags(tags) {
		var el = tagsEl();
		if (!el || !tags || !tags.length || !window.jQuery) {
			return false;
		}
		var $el = window.jQuery(el);
		tags.forEach(function (tag) {
			var exists = Array.prototype.some.call(el.options, function (o) {
				return o.value.toLowerCase() === tag.toLowerCase();
			});
			if (!exists) {
				$el.append(new Option(tag, tag, true, true));
			} else {
				Array.prototype.forEach.call(el.options, function (o) {
					if (o.value.toLowerCase() === tag.toLowerCase()) {
						o.selected = true;
					}
				});
			}
		});
		$el.trigger('change');
		highlight(el);
		return true;
	}

	// ---------- undo ----------

	function takeSnapshot() {
		var title = q('[name="title"]');
		var price = q('[name="price"]');
		var negotiable = q('[name="negotiable"]');
		var tags = tagsEl();
		return {
			title: title ? title.value : null,
			description: getDescription(),
			price: price ? price.value : null,
			negotiable: negotiable ? negotiable.checked : null,
			categoryHtml: (document.getElementById('catsContainer') || {}).innerHTML,
			categoryId: (q('#categoryId') || {}).value,
			categoryType: (q('#categoryType') || {}).value,
			city: ['selectedAdminType', 'selectedAdminCode', 'selectedCityId', 'selectedCityName'].reduce(function (acc, id) {
				var el = document.getElementById(id);
				acc[id] = el ? el.value : null;
				return acc;
			}, {cityHtml: (document.getElementById('cityId') || {}).innerHTML}),
			tags: tags ? Array.prototype.map.call(tags.options, function (o) {
				return {value: o.value, text: o.text, selected: o.selected};
			}) : null
		};
	}

	function restore() {
		if (!snapshot) {
			return;
		}
		var s = snapshot;
		if (s.title !== null) {
			setValue(q('[name="title"]'), s.title);
		}
		var ed = editor();
		var el = descriptionEl();
		if (ed) {
			ed.setContent(s.description || '');
			ed.save();
		} else if (el) {
			el.value = s.description || '';
		}
		fire(el);
		if (s.price !== null) {
			setValue(q('[name="price"]'), s.price);
		}
		if (s.negotiable !== null) {
			setValue(q('[name="negotiable"]'), s.negotiable);
		}
		var tags = tagsEl();
		if (tags && s.tags && window.jQuery) {
			var $tags = window.jQuery(tags).empty();
			s.tags.forEach(function (o) {
				$tags.append(new Option(o.text, o.value, o.selected, o.selected));
			});
			$tags.trigger('change');
		}
		if (s.categoryId !== (q('#categoryId') || {}).value) {
			if (s.categoryId && s.categoryId !== '0' && typeof window.appendSelectedCategory === 'function') {
				window.appendSelectedCategory(window.siteUrl, window.languageCode, s.categoryId, s.categoryType, s.categoryHtml, true);
			} else {
				var cats = document.getElementById('catsContainer');
				if (cats) {
					cats.innerHTML = s.categoryHtml;
				}
				if (q('#categoryId')) {
					q('#categoryId').value = s.categoryId || 0;
				}
				var cf = document.getElementById('cfContainer');
				if (cf) {
					cf.innerHTML = '';
				}
			}
		}
		if (s.city) {
			Object.keys(s.city).forEach(function (id) {
				var el = document.getElementById(id);
				if (el && id !== 'cityHtml' && s.city[id] !== null) {
					el.value = s.city[id];
				}
			});
			var cityEl = document.getElementById('cityId');
			if (cityEl) {
				cityEl.innerHTML = s.city.cityHtml || '';
				fire(cityEl);
			}
		}
		snapshot = null;
		undoButton.classList.add('d-none');
		resultEl.classList.add('d-none');
		setMessage(plain('restored'), 'secondary');
	}

	// ---------- result checklist ----------

	function row(ok, html) {
		return '<li class="list-group-item px-0 py-1 border-0 d-flex gap-2">'
			+ (ok ? '<i class="fa-solid fa-circle-check text-success mt-1"></i>' : '<i class="fa-solid fa-circle-exclamation text-warning mt-1"></i>')
			+ '<span>' + html + '</span></li>';
	}

	function showResult(data, filledCity, transcript, photosAdded) {
		var items = [];
		items.push(data.category
			? row(true, t('row_category', {category: data.category.path}))
			: row(false, t('row_no_category')));
		items.push(row(!!data.title, data.title ? t('row_title') : t('row_no_title')));
		items.push(data.price
			? row(true, t('row_price', {price: data.price}))
			: row(false, t('row_no_price')));
		if (data.cf_labels && data.cf_labels.length) {
			items.push(row(true, t('row_details', {details: data.cf_labels.join(', ')})));
		} else if (data.category) {
			items.push(row(false, t('row_check_details')));
		}
		items.push(filledCity
			? row(true, t('row_city', {city: data.city.name}))
			: row(false, data.city_name ? t('row_city_unknown', {city: data.city_name}) : t('row_no_city')));
		if (data.tags && data.tags.length) {
			items.push(row(true, t('row_tags', {tags: data.tags.join(', ')})));
		}
		if (photosAdded > 0) {
			items.push(row(true, t('row_photos', {count: photosAdded})));
		}
		if (transcript) {
			items.push('<li class="list-group-item px-0 py-1 border-0 small text-secondary">' + t('row_heard', {text: transcript}) + '</li>');
		}
		resultEl.innerHTML = '<div class="small fw-bold mb-1">' + t('done') + '</div><ul class="list-group list-group-flush small">' + items.join('') + '</ul>';
		resultEl.classList.remove('d-none');
	}

	// ---------- apply ----------

	function setPostType(postTypeId) {
		if (!postTypeId) {
			return;
		}
		var radio = q('input[name="post_type_id"][value="' + parseInt(postTypeId, 10) + '"]');
		if (radio) {
			setValue(radio, true);
		} else {
			setValue(q('select[name="post_type_id"]'), postTypeId);
		}
	}

	function apply(data, transcript, photosAdded) {
		snapshot = takeSnapshot();

		if (data.category) {
			var current = (q('#categoryId') || {}).value;
			if (String(current) !== String(data.category.id)) {
				whenFieldsLoaded(function () {
					fillDetails(data.cf);
				});
				selectCategory(data.category);
			} else {
				fillDetails(data.cf);
			}
		}
		if (data.title) {
			setValue(q('[name="title"]'), data.title);
		}
		if (data.html || data.text) {
			setDescription(data.html, data.text);
		}
		if (data.price) {
			setValue(q('[name="price"]'), data.price);
		}
		setPostType(data.post_type_id);
		var filledCity = setCity(data.city);
		setTags(data.tags);

		if (transcript && !textEl.value.trim()) {
			textEl.value = transcript;
		}
		showResult(data, filledCity, transcript, photosAdded || 0);
		undoButton.classList.remove('d-none');
		setMessage('');
	}

	// ---------- requests ----------

	function send(body) {
		var token = document.querySelector('meta[name="csrf-token"]');
		var controller = window.AbortController ? new AbortController() : null;
		var timeout = setTimeout(function () {
			if (controller) {
				controller.abort();
			}
		}, 90000);
		if (lastDraft) {
			body.append('redraft_of', lastDraft);
		}
		photos.forEach(function (photo) {
			body.append('photos[]', photo.file, photo.file.name);
		});

		return fetch(box.dataset.url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			},
			body: body,
			signal: controller ? controller.signal : undefined
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (json) {
				return {ok: response.ok, data: json};
			});
		}).then(function (res) {
			setBusy(false);
			if (res.data && res.data.limit_text) {
				updateUsage(res.data.limit_text);
			}
			if (!res.ok || !res.data.ok || !res.data.fields) {
				var errors = res.data && res.data.errors ? Object.values(res.data.errors)[0] : null;
				setMessage((errors && errors[0]) || (res.data && res.data.message) || plain('error'), 'danger');
				if (res.data && (res.data.reason === 'limit' || res.data.reason === 'off')) {
					fillButton.disabled = true;
					textEl.disabled = true;
					if (speakButton) {
						speakButton.disabled = true;
					}
				}
				return;
			}
			lastDraft = res.data.draft || lastDraft;
			apply(res.data.fields, res.data.transcript, res.data.photos_added);
		}).catch(function () {
			setBusy(false);
			setMessage(plain('timeout'), 'danger');
		}).finally(function () {
			clearTimeout(timeout);
		});
	}

	function hasContent() {
		var title = q('[name="title"]');
		var plain = (getDescription() || '').replace(/<[^>]*>/g, '').trim();
		return (title && title.value.trim() !== '') || plain !== '';
	}

	/* Asks before the AI overwrites what the seller typed (SweetAlert2); resolves true to go on */
	function confirmReplace() {
		if (!hasContent()) {
			return Promise.resolve(true);
		}
		// SweetAlert2 is on every page; should it fail to load, still never overwrite silently
		if (typeof window.Swal === 'undefined') {
			return Promise.resolve(window.confirm(plain('confirm_replace')));
		}
		var buttons = (window.langLayout && window.langLayout.confirm && window.langLayout.confirm.button) || {};
		return window.Swal.fire({
			text: plain('confirm_replace'),
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: buttons.ok || 'OK',
			cancelButtonText: buttons.cancel || 'Cancel',
			theme: (typeof window.isDarkThemeEnabled === 'function' && window.isDarkThemeEnabled()) ? 'dark' : 'light'
		}).then(function (result) {
			return !!result.isConfirmed;
		});
	}

	fillButton.addEventListener('click', function () {
		if (busy) {
			return;
		}
		var text = textEl.value.trim();
		if (text.length < 8 && photos.length === 0) {
			setMessage(plain('too_short'), 'warning');
			textEl.focus();
			return;
		}
		confirmReplace().then(function (go) {
			if (!go) {
				return;
			}
			setMessage('');
			setBusy(true, plain('filling'));
			var body = new FormData();
			if (text !== '') {
				body.append('text', text);
			}
			send(body);
		});
	});

	textEl.addEventListener('keydown', function (event) {
		if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
			event.preventDefault();
			fillButton.click();
		}
	});

	undoButton.addEventListener('click', restore);

	// ---------- photos ----------

	var SHRINK_EDGE = 1600; // px, long edge; the AI and the listing need no more
	var SHRINK_QUALITY = 0.85;

	/** A copy of a large photo, resized to JPEG in the browser; the original when it is small or cannot be read. */
	function shrink(file) {
		return new Promise(function (resolve) {
			if (!/^image\//.test(file.type) || !window.URL || !document.createElement('canvas').toBlob) {
				resolve(file);
				return;
			}
			var url = URL.createObjectURL(file);
			var img = new Image();
			img.onload = function () {
				var scale = Math.min(1, SHRINK_EDGE / Math.max(img.naturalWidth, img.naturalHeight));
				URL.revokeObjectURL(url);
				if (scale === 1 && file.size <= maxPhotoBytes) {
					resolve(file);
					return;
				}
				var canvas = document.createElement('canvas');
				canvas.width = Math.round(img.naturalWidth * scale);
				canvas.height = Math.round(img.naturalHeight * scale);
				canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
				canvas.toBlob(function (blob) {
					var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
					resolve(blob ? new File([blob], name, {type: 'image/jpeg'}) : file);
				}, 'image/jpeg', SHRINK_QUALITY);
			};
			img.onerror = function () {
				URL.revokeObjectURL(url);
				resolve(file);
			};
			img.src = url;
		});
	}

	function renderThumbs() {
		thumbsEl.textContent = '';
		photos.forEach(function (photo, index) {
			var item = document.createElement('div');
			item.className = 'ai-assistant-thumb';
			var img = document.createElement('img');
			img.src = photo.url;
			img.alt = '';
			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'ai-assistant-thumb-remove';
			remove.setAttribute('aria-label', plain('photo_remove'));
			remove.title = plain('photo_remove');
			remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
			remove.addEventListener('click', function () {
				URL.revokeObjectURL(photo.url);
				photos.splice(index, 1);
				renderThumbs();
			});
			item.appendChild(img);
			item.appendChild(remove);
			thumbsEl.appendChild(item);
		});
		thumbsEl.classList.toggle('d-none', photos.length === 0);
		photoAddButton.classList.toggle('d-none', photos.length >= maxPhotos);
		if (cameraButton && cameraButton.dataset.aiCameraFound === '1') {
			cameraButton.classList.toggle('d-none', photos.length >= maxPhotos);
		}
	}

	if (photoInput && photoAddButton && thumbsEl) {
		photoAddButton.addEventListener('click', function () {
			if (!busy) {
				photoInput.click();
			}
		});
		photoInput.addEventListener('change', function () {
			var chosen = Array.prototype.slice.call(photoInput.files || []);
			photoInput.value = '';
			addPhotos(chosen);
		});
	}

	/** Chosen, phone-camera and webcam photos alike: within the limit, resized, size-checked, then shown */
	function addPhotos(chosen) {
		var room = maxPhotos - photos.length;
		if (chosen.length > room) {
			setMessage(plain('photos_max').replace(':max', String(maxPhotos)), 'warning');
		} else {
			setMessage('');
		}
		return Promise.all(chosen.slice(0, Math.max(0, room)).map(shrink)).then(function (files) {
			files.forEach(function (file) {
				if (file.size > maxPhotoBytes) {
					setMessage(plain('photo_too_big').replace(':size', String(Math.round(maxPhotoBytes / 104857.6) / 10)), 'warning');
					return;
				}
				photos.push({file: file, url: URL.createObjectURL(file)});
			});
			renderThumbs();
		});
	}

	// ---------- taking a photo with the camera ----------
	// Phones and tablets open their camera app (the input's capture attribute); computers with a webcam
	// get a small camera window. The button stays hidden on devices without a camera.

	var cameraButton = box.querySelector('[data-ai-camera]');
	var cameraInput = box.querySelector('.ai-assistant-camera-input');
	var cameraModalEl = document.getElementById('aiCameraModal');
	var CAMERA_ERRORS = {
		NotAllowedError: 'camera_denied',
		PermissionDeniedError: 'camera_denied',
		SecurityError: 'camera_denied',
		NotFoundError: 'camera_missing',
		DevicesNotFoundError: 'camera_missing',
		OverconstrainedError: 'camera_missing'
	};
	var CAPTURE_QUALITY = 0.92;
	var isTouch = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
	var canStream = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.isSecureContext);
	var cameraStream = null;
	var facingMode = 'environment';
	var cameraModal = null;
	var cameraVideo = cameraModalEl ? cameraModalEl.querySelector('.ai-camera-video') : null;
	var switchButton = cameraModalEl ? cameraModalEl.querySelector('.ai-camera-switch') : null;

	function hasCamera() {
		if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
			return Promise.resolve(isTouch);
		}
		return navigator.mediaDevices.enumerateDevices().then(function (devices) {
			return devices.some(function (device) {
				return device.kind === 'videoinput';
			});
		}, function () {
			return isTouch;
		});
	}

	function stopCamera() {
		if (cameraStream) {
			cameraStream.getTracks().forEach(function (track) {
				track.stop();
			});
			cameraStream = null;
		}
		if (cameraVideo) {
			cameraVideo.srcObject = null;
		}
	}

	function startCamera() {
		stopCamera();
		return navigator.mediaDevices.getUserMedia({video: {facingMode: facingMode, width: {ideal: 1920}, height: {ideal: 1080}}, audio: false})
			.then(function (stream) {
				cameraStream = stream;
				cameraVideo.srcObject = stream;
				var settings = stream.getVideoTracks()[0] ? stream.getVideoTracks()[0].getSettings() : {};
				// Webcams face the person (no facingMode reported on most computers); a back camera does not
				cameraVideo.classList.toggle('is-mirrored', settings.facingMode ? settings.facingMode === 'user' : true);
				return cameraVideo.play().catch(function () {
				});
			});
	}

	/** Several cameras (front and back, or a laptop plus a USB webcam): offer to switch */
	function showSwitchWhenUseful() {
		navigator.mediaDevices.enumerateDevices().then(function (devices) {
			var count = devices.filter(function (device) {
				return device.kind === 'videoinput';
			}).length;
			switchButton.classList.toggle('d-none', count < 2);
		}, function () {
		});
	}

	function openCameraWindow() {
		if (photos.length >= maxPhotos) {
			return;
		}
		// Still closing after the last photo: open again once it has closed (closing stops the camera)
		if (cameraModalEl.classList.contains('show') || cameraModalEl.style.display === 'block') {
			cameraModalEl.addEventListener('hidden.bs.modal', openCameraWindow, {once: true});
			return;
		}
		startCamera().then(function () {
			if (!cameraModal) {
				document.body.appendChild(cameraModalEl); // above the form, whatever its layout
				cameraModal = window.bootstrap.Modal.getOrCreateInstance(cameraModalEl);
			}
			cameraModal.show();
			showSwitchWhenUseful();
		}, function (error) {
			var cameraKey = CAMERA_ERRORS[error && error.name] || 'camera_busy';
			setMessage(plain(cameraKey), 'danger');
			showCameraHelp(cameraKey);
		});
	}

	function capturePhoto() {
		var width = cameraVideo.videoWidth;
		var height = cameraVideo.videoHeight;
		if (!width || !height) {
			return;
		}
		var canvas = document.createElement('canvas');
		canvas.width = width;
		canvas.height = height;
		canvas.getContext('2d').drawImage(cameraVideo, 0, 0, width, height);
		canvas.toBlob(function (blob) {
			if (blob) {
				addPhotos([new File([blob], 'camera-' + Date.now() + '.jpg', {type: 'image/jpeg'})]);
			}
			cameraModal.hide();
		}, 'image/jpeg', CAPTURE_QUALITY);
	}

	if (cameraButton && cameraInput && thumbsEl) {
		hasCamera().then(function (found) {
			cameraButton.classList.toggle('d-none', !found || photos.length >= maxPhotos);
			cameraButton.dataset.aiCameraFound = found ? '1' : '0';
		});
		cameraButton.addEventListener('click', function () {
			if (busy) {
				return;
			}
			if (photos.length >= maxPhotos) {
				setMessage(plain('photos_max').replace(':max', String(maxPhotos)), 'warning');
				return;
			}
			if (isTouch || !canStream || !cameraModalEl || !window.bootstrap) {
				cameraInput.click();
				return;
			}
			openCameraWindow();
		});
		cameraInput.addEventListener('change', function () {
			var taken = Array.prototype.slice.call(cameraInput.files || []);
			cameraInput.value = '';
			addPhotos(taken);
		});
		if (cameraModalEl) {
			cameraModalEl.querySelector('.ai-camera-shoot').addEventListener('click', capturePhoto);
			switchButton.addEventListener('click', function () {
				facingMode = facingMode === 'environment' ? 'user' : 'environment';
				startCamera().catch(function () {
				});
			});
			// Capture only once the window has finished opening (a close request while it opens is ignored)
			var shootButton = cameraModalEl.querySelector('.ai-camera-shoot');
			cameraModalEl.addEventListener('show.bs.modal', function () {
				shootButton.disabled = true;
			});
			cameraModalEl.addEventListener('shown.bs.modal', function () {
				shootButton.disabled = false;
				shootButton.focus();
			});
			cameraModalEl.addEventListener('hidden.bs.modal', function () {
				stopCamera();
			});
		}
		window.addEventListener('beforeunload', stopCamera);
	}

	// Example chips: show what a good description looks like
	Array.prototype.forEach.call(box.querySelectorAll('.ai-assistant-chip'), function (chip) {
		chip.addEventListener('click', function () {
			if (textEl.disabled) {
				return;
			}
			textEl.value = chip.dataset.example || '';
			textEl.focus();
			setMessage('');
		});
	});

	// ---------- speaking (WhatsApp-style voice bar) ----------

	var canRecord = !!(speakButton && box.dataset.serverVoice === '1' && window.MediaRecorder && navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.isSecureContext);
	if (canRecord) {
		speakButton.classList.remove('d-none');
		var recorder = null;
		var stream = null;
		var chunks = [];
		var startedAt = 0;
		var tick = null;
		var cancelled = false;
		var waveCanvas = box.querySelector('.ai-voice-wave');
		var audioCtx = null;
		var analyser = null;
		var frame = null;
		var levels = [];
		var paused = false;
		var pausedFor = 0;
		var pausedAt = 0;
		var pauseButton = box.querySelector('.ai-voice-pause');
		var elapsedSeconds = function () {
			var now = paused ? pausedAt : Date.now();
			return (now - startedAt - pausedFor) / 1000;
		};

		var mimeType = function () {
			var types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg'];
			for (var i = 0; i < types.length; i++) {
				if (window.MediaRecorder.isTypeSupported && window.MediaRecorder.isTypeSupported(types[i])) {
					return types[i];
				}
			}
			return '';
		};
		var clock = function (s) {
			s = Math.max(0, Math.floor(s));
			return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
		};
		var brandColor = function () {
			var c = getComputedStyle(box).getPropertyValue('--lf-brand').trim() || getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim();
			return c || '#0a837f';
		};

		// WhatsApp-style: bars grow from the left (then scroll), quiet moments are small dots
		var drawWave = function () {
			if (!waveCanvas) {
				return;
			}
			var dpr = window.devicePixelRatio || 1;
			var w = Math.max(1, Math.round(waveCanvas.clientWidth * dpr));
			var h = Math.max(1, Math.round(waveCanvas.clientHeight * dpr));
			if (waveCanvas.width !== w || waveCanvas.height !== h) {
				waveCanvas.width = w;
				waveCanvas.height = h;
			}
			var g = waveCanvas.getContext('2d');
			g.clearRect(0, 0, w, h);
			var barW = 3 * dpr;
			var gap = 2.5 * dpr;
			var step = barW + gap;
			var count = Math.max(1, Math.floor(w / step));
			var shown = levels.slice(-count);
			g.fillStyle = paused ? '#9aaaa8' : brandColor();
			for (var i = 0; i < shown.length; i++) {
				var x = i * step;
				var level = shown[i];
				if (level < 0.07) {
					g.beginPath();
					g.arc(x + barW / 2, h / 2, 1.3 * dpr, 0, Math.PI * 2);
					g.fill();
					continue;
				}
				var bh = Math.max(4 * dpr, level * h);
				var y = (h - bh) / 2;
				if (g.roundRect) {
					g.beginPath();
					g.roundRect(x, y, barW, bh, barW / 2);
					g.fill();
				} else {
					g.fillRect(x, y, barW, bh);
				}
			}
		};
		var startWave = function (media) {
			levels = [];
			drawWave();
			try {
				var AC = window.AudioContext || window.webkitAudioContext;
				if (!AC) {
					return;
				}
				audioCtx = new AC();
				analyser = audioCtx.createAnalyser();
				analyser.fftSize = 1024;
				audioCtx.createMediaStreamSource(media).connect(analyser);
				var data = new Uint8Array(analyser.fftSize);
				frame = setInterval(function () {
					if (paused) {
						return;
					}
					analyser.getByteTimeDomainData(data);
					var sum = 0;
					for (var i = 0; i < data.length; i++) {
						var v = (data[i] - 128) / 128;
						sum += v * v;
					}
					var rms = Math.sqrt(sum / data.length);
					levels.push(Math.min(1, Math.pow(rms * 3.5, .85)));
					if (levels.length > 1500) {
						levels.shift();
					}
					drawWave();
				}, 80);
			} catch (e) {
			}
		};
		var stopWave = function () {
			if (frame) {
				clearInterval(frame);
				frame = null;
			}
			if (audioCtx) {
				try {
					audioCtx.close();
				} catch (e) {
				}
				audioCtx = null;
			}
		};
		var stopAll = function () {
			stopWave();
			if (stream) {
				stream.getTracks().forEach(function (t) {
					t.stop();
				});
				stream = null;
			}
			if (tick) {
				clearInterval(tick);
				tick = null;
			}
		};
		var setPaused = function (state) {
			paused = state;
			box.classList.toggle('is-paused', state);
			if (pauseButton) {
				pauseButton.innerHTML = state ? '<i class="fa-solid fa-microphone"></i>' : '<i class="fa-solid fa-pause"></i>';
				pauseButton.setAttribute('aria-label', plain(state ? 'rec_continue' : 'rec_pause'));
				pauseButton.title = plain(state ? 'rec_continue' : 'rec_pause');
			}
			drawWave();
		};
		var hideBar = function () {
			setPaused(false);
			box.classList.remove('is-recording', 'is-sending', 'is-paused');
			recordingEl.classList.add('d-none');
			stopButton.disabled = false;
		};
		var stop = function () {
			if (recorder && recorder.state !== 'inactive') {
				recorder.stop();
			}
		};

		speakButton.addEventListener('click', function () {
			if (busy || recorder) {
				return;
			}
			confirmReplace().then(function (go) {
				if (go && !busy && !recorder) {
					startRecording();
				}
			});
		});
		function startRecording() {
			setMessage('');
			chunks = [];
			cancelled = false;
			navigator.mediaDevices.getUserMedia({audio: true}).then(function (media) {
				stream = media;
				var type = mimeType();
				try {
					recorder = type ? new window.MediaRecorder(media, {mimeType: type}) : new window.MediaRecorder(media);
				} catch (e) {
					recorder = new window.MediaRecorder(media);
				}
				recorder.addEventListener('dataavailable', function (e) {
					if (e.data && e.data.size > 0) {
						chunks.push(e.data);
					}
				});
				recorder.addEventListener('stop', function () {
					var recordedType = (recorder && recorder.mimeType) || type || 'audio/webm';
					var seconds = elapsedSeconds();
					recorder = null;
					stopAll();
					if (cancelled) {
						hideBar();
						setBusy(false);
						return;
					}
					if (seconds < 2 || !chunks.length) {
						hideBar();
						setBusy(false);
						setMessage(plain('rec_short'), 'warning');
						return;
					}
					// Keep the bar, frozen, while the recording is turned into a listing
					box.classList.add('is-sending');
					stopButton.disabled = true;
					setBusy(true, plain('filling'));
					var ext = recordedType.indexOf('mp4') !== -1 ? 'm4a' : (recordedType.indexOf('ogg') !== -1 ? 'ogg' : 'webm');
					var body = new FormData();
					body.append('audio', new Blob(chunks, {type: recordedType.split(';')[0]}), 'voice.' + ext);
					send(body).finally(hideBar);
				});
				recorder.start(1000);
				startedAt = Date.now();
				pausedFor = 0;
				setPaused(false);
				timerEl.textContent = '0:00';
				box.classList.add('is-recording');
				recordingEl.classList.remove('d-none');
				setBusy(true, plain('listening'));
				startWave(media);
				tick = setInterval(function () {
					var elapsed = elapsedSeconds();
					timerEl.textContent = clock(elapsed);
					if (elapsed >= maxSeconds) {
						stop();
					}
				}, 250);
			}, function (error) {
				// NotReadableError, AbortError and the like: the microphone exists but would not start
				var micKey = MIC_ERRORS[error && error.name] || 'mic_busy';
				setMessage(plain(micKey), 'danger');
				showMicHelp(micKey);
			}).catch(function () {
				// The microphone was allowed, but recording could not start
				cancelled = true;
				if (recorder && recorder.state !== 'inactive') {
					recorder.stop();
				} else {
					recorder = null;
					stopAll();
					hideBar();
					setBusy(false);
				}
				setMessage(plain('mic_busy'), 'danger');
				showMicHelp('mic_busy');
			});
		}
		var stopRecording = function () {
			// Resume first so the last part is flushed and the length is right
			if (paused && recorder && recorder.state === 'paused') {
				pausedFor += Date.now() - pausedAt;
				recorder.resume();
				paused = false;
			}
			stop();
		};
		stopButton.addEventListener('click', stopRecording);
		if (pauseButton) {
			pauseButton.addEventListener('click', function () {
				if (!recorder || box.classList.contains('is-sending')) {
					return;
				}
				if (!paused && recorder.state === 'recording' && recorder.pause) {
					recorder.pause();
					pausedAt = Date.now();
					setPaused(true);
				} else if (paused && recorder.state === 'paused') {
					recorder.resume();
					pausedFor += Date.now() - pausedAt;
					setPaused(false);
				}
			});
		}
		cancelButton.addEventListener('click', function () {
			cancelled = true;
			stop();
		});
		window.addEventListener('beforeunload', stopAll);
	}

	// ---------- dictation with the browser's own speech recognition (no speech model on the server) ----------

	var Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
	if (speakButton && !canRecord && Recognition && window.isSecureContext) {
		speakButton.classList.remove('d-none');
		var recognition = null;
		var stopListening = function () {
			if (recognition) {
				recognition.stop();
			}
		};
		speakButton.addEventListener('click', function () {
			if (recognition) {
				stopListening();
				return;
			}
			var before = textEl.value.trim();
			var heard = '';
			recognition = new Recognition();
			recognition.lang = box.dataset.speechLang || document.documentElement.lang || 'en';
			recognition.interimResults = true;
			recognition.continuous = true;
			recognition.addEventListener('result', function (event) {
				var finalText = '';
				var interim = '';
				for (var i = 0; i < event.results.length; i++) {
					if (event.results[i].isFinal) {
						finalText += event.results[i][0].transcript;
					} else {
						interim += event.results[i][0].transcript;
					}
				}
				heard = (finalText + ' ' + interim).trim();
				textEl.value = (before ? before + ' ' : '') + heard;
			});
			recognition.addEventListener('error', function (event) {
				var micKey = MIC_ERRORS[event.error];
				setMessage(plain(micKey || 'no_speech'), 'warning');
				if (micKey) {
					showMicHelp(micKey);
				}
			});
			recognition.addEventListener('end', function () {
				recognition = null;
				speakButton.classList.remove('is-listening');
				speakButton.setAttribute('aria-pressed', 'false');
			});
			setMessage('');
			speakButton.classList.add('is-listening');
			speakButton.setAttribute('aria-pressed', 'true');
			recognition.start();
			setTimeout(stopListening, maxSeconds * 1000);
		});
	}
})();
