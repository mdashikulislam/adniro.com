{{--
	Homepage: "Recently viewed" listings, in one row.
	The listings opened by the visitor are remembered in the browser (localStorage, see the listing page).
	The section stays hidden for the visitors without history (& for the search engines).
--}}
<div class="container recently-viewed-section" id="recentlyViewed" hidden
     data-url="{{ url('listings/recently-viewed') }}"
     data-storage-key="adn_recently_viewed"
>
	<div class="card listing-section">
		<div class="card-header border-bottom-0 section-header d-flex align-items-center justify-content-between flex-wrap gap-2">
			<h4 class="mb-0 fw-lighter section-title">{!! t('home_recently_viewed') !!}</h4>
			<div class="recently-viewed-tools">
				<button type="button" class="btn recently-viewed-nav" id="recentlyViewedPrev" aria-label="{{ t('previous') }}" hidden>
					<i class="bi bi-chevron-left"></i>
				</button>
				<button type="button" class="btn recently-viewed-nav" id="recentlyViewedNext" aria-label="{{ t('next') }}" hidden>
					<i class="bi bi-chevron-right"></i>
				</button>
				<button type="button" class="btn section-more-link recently-viewed-clear" id="recentlyViewedClear">
					<i class="bi bi-x-lg"></i> {{ t('clear_history') }}
				</button>
			</div>
		</div>
		<div class="card-body rounded py-0">
			<div class="recently-viewed-row" id="recentlyViewedRow">
				{{-- Placeholder cards: the row's height is reserved before the cards arrive (no layout shift) --}}
				<div class="recently-viewed-skeleton" aria-hidden="true">
					@for ($i = 0; $i < 4; $i++)
						<span class="recently-viewed-skeleton-card"></span>
					@endfor
				</div>
			</div>
		</div>
	</div>
</div>
{{-- Runs while the page is parsed (before the first paint): the section is shown at once for the visitors having a history --}}
<script>
	(function () {
		var sectionEl = document.getElementById('recentlyViewed');
		if (!sectionEl) {
			return;
		}
		var storageKey = sectionEl.dataset.storageKey;
		var readIds = function () {
			try {
				var ids = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
				return Array.isArray(ids) ? ids.filter(function (id) { return /^\d+$/.test(String(id)); }) : [];
			} catch (e) {
				return [];
			}
		};
		var writeIds = function (ids) {
			try {
				if (ids.length > 0) {
					window.localStorage.setItem(storageKey, JSON.stringify(ids));
				} else {
					window.localStorage.removeItem(storageKey);
				}
			} catch (e) {
			}
		};
		
		var ids = readIds();
		if (ids.length === 0) {
			return;
		}
		sectionEl.hidden = false;
		
		var rowEl = document.getElementById('recentlyViewedRow');
		var prevBtn = document.getElementById('recentlyViewedPrev');
		var nextBtn = document.getElementById('recentlyViewedNext');
		var clearBtn = document.getElementById('recentlyViewedClear');
		
		var scrollerEl = function () {
			return rowEl.querySelector('.row');
		};
		var refreshNav = function () {
			var el = scrollerEl();
			if (!el) {
				return;
			}
			var isScrollable = (el.scrollWidth - el.clientWidth) > 4;
			prevBtn.hidden = !isScrollable;
			nextBtn.hidden = !isScrollable;
			var start = Math.abs(el.scrollLeft);
			prevBtn.disabled = (start <= 2);
			nextBtn.disabled = (start + el.clientWidth >= el.scrollWidth - 2);
		};
		var scrollByPage = function (direction) {
			var el = scrollerEl();
			if (!el) {
				return;
			}
			var isRtl = (getComputedStyle(el).direction === 'rtl');
			el.scrollBy({left: direction * (isRtl ? -1 : 1) * el.clientWidth * 0.9, behavior: 'smooth'});
		};
		prevBtn.addEventListener('click', function () { scrollByPage(-1); });
		nextBtn.addEventListener('click', function () { scrollByPage(1); });
		clearBtn.addEventListener('click', function () {
			writeIds([]);
			sectionEl.hidden = true;
		});
		window.addEventListener('resize', refreshNav);
		
		fetch(sectionEl.dataset.url + '?ids=' + encodeURIComponent(ids.join(',')), {
			credentials: 'same-origin',
			headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
		})
			.then(function (response) {
				return response.ok ? response.json() : Promise.reject(response.status);
			})
			.then(function (json) {
				if (!json || !json.html || !json.count) {
					writeIds([]);
					sectionEl.hidden = true;
					return;
				}
				
				{{-- The listings that are no longer online are forgotten --}}
				if (Array.isArray(json.ids)) {
					writeIds(json.ids.map(String));
				}
				
				rowEl.innerHTML = json.html;
				
				{{-- "Save" buttons of the new cards (the page's ones are bound when it's loaded) --}}
				rowEl.querySelectorAll('a[id].make-favorite').forEach(function (el) {
					el.addEventListener('click', function (e) {
						e.preventDefault();
						if (typeof isLoggedUser === 'undefined' || isLoggedUser !== true) {
							if (typeof openLoginModal === 'function') {
								openLoginModal();
							}
							return false;
						}
						return (typeof savePost === 'function') ? savePost(e.target) : false;
					});
				});
				
				var el = scrollerEl();
				if (el) {
					el.addEventListener('scroll', refreshNav, {passive: true});
				}
				refreshNav();
			})
			.catch(function () {
				sectionEl.hidden = true;
			});
	})();
</script>
