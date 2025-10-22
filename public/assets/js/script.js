/*
 * Prevent errors if these variables are missing
 */
if (typeof maxSubCats === 'undefined') {
	var maxSubCats = 3;
}

onDocumentReady((event) => {
	
	/* Enable tooltips everywhere */
	initElementTooltips(getHtmlElement()); /* Default trigger: 'hover focus' */
	initElementTooltips(getHtmlElement(), {trigger: 'hover'}, 'tooltipHover');
	
	/* Enable poppers everywhere */
	initElementPopovers(getHtmlElement(), {html: true});
	
	/* Change a tooltip size in Bootstrap 4.x */
	const locSearchEl = document.getElementById('locSearch');
	if (locSearchEl) {
		const tooltipEvents = ['mouseover', 'mouseenter', 'mouseleave', 'mousemove'];
		tooltipEvents.forEach((event) => {
			locSearchEl.addEventListener(event, applyTooltipStyles);
		});
	}
	
	/* ============================================== */
	/* Global Plugins
	/* ============================================== */
	hideMaxListItems('.long-list', {
		max: 8,
		speed: 500,
		moreText: langLayout.hideMaxListItems.moreText + ' ([COUNT])',
		lessText: langLayout.hideMaxListItems.lessText
	});
	hideMaxListItems('.long-list-user', {
		max: 12,
		speed: 500,
		moreText: langLayout.hideMaxListItems.moreText + ' ([COUNT])',
		lessText: langLayout.hideMaxListItems.lessText
	});
	hideMaxListItems('.long-list-home', {
		max: maxSubCats,
		speed: 500,
		moreText: langLayout.hideMaxListItems.moreText + ' ([COUNT])',
		lessText: langLayout.hideMaxListItems.lessText
	});
	
	/* Bootstrap Collapse + jQuery hideMaxListItem fix on mobile */
	$('.btn-cat-collapsed').click(function () {
		const targetSelector = $(this).data('target');
		const isExpanded = $(this).attr('aria-expanded');
		
		if (typeof isExpanded === 'undefined') {
			return false;
		}
		
		$(targetSelector).toggle('slow');
		
		if (isExpanded === 'true' || isExpanded === true) {
			$('.cat-list ' + targetSelector).next('.maxlist-more').hide();
		} else {
			$('.cat-list ' + targetSelector).next('.maxlist-more').show();
		}
	});
	
	/* Jobs */
	$("input:radio").click(function () {
		if ($('input:radio#job-seeker:checked').length > 0) {
			$('.forJobSeeker').removeClass('hide');
			$('.forJobFinder').addClass('hide');
		} else {
			$('.forJobFinder').removeClass('hide');
			$('.forJobSeeker').addClass('hide')
		}
	});
	
	/* INBOX MESSAGE */
	/* Check 'assets/js/app/messenger.js' */
	
	/* Check New Messages */
	/* 60000 = 60 seconds (Timer) */
	if (typeof timerNewMessagesChecking !== 'undefined') {
		checkNewMessages();
		if (timerNewMessagesChecking > 0) {
			setInterval(() => checkNewMessages(), timerNewMessagesChecking);
		}
	}
	
	/* Data loading-mask pre-configuration */
	$.busyLoadSetup({
		background: 'rgba(0, 0, 0, 0.05)',
		animation: 'fade',
		spinner: 'pump',
		color: '#666',
		textPosition: 'left'
	});
});

function createCustomSpinnerEl() {
	return $('<div>', {
		class: 'spinner-border',
		css: {'width': '30px', 'height': '30px'}
	});
}

/**
 * Change a tooltip size in Bootstrap 4.x
 */
function applyTooltipStyles() {
	const tooltipInnerEls = document.querySelectorAll('.tooltip-inner');
	if (tooltipInnerEls.length > 0) {
		tooltipInnerEls.forEach((element) => {
			element.style.width = "300px";
			element.style.maxWidth = "300px";
		});
	}
}

/**
 * Set Country Phone Code
 * @param countryCode
 * @param countries
 * @returns {boolean}
 */
function setCountryPhoneCode(countryCode, countries) {
	if (typeof countryCode === "undefined" || typeof countries === "undefined") return false;
	if (typeof countries[countryCode] === "undefined") return false;
	
	const phoneCountryEl = document.getElementById('phoneCountry');
	if (phoneCountryEl) {
		phoneCountryEl.innerHTML = countries[countryCode]['phone'];
	}
}

/**
 * Check Threads with New Messages
 */
function checkNewMessages() {
	const countThreadWithNewMessageEl = $('.count-threads-with-new-messages');
	if (countThreadWithNewMessageEl.length <= 0) {
		return;
	}
	
	const firstEl = countThreadWithNewMessageEl.get(0);
	const $firstEl = $(firstEl);
	
	let oldValue = $firstEl.html();
	if (typeof oldValue === 'undefined') {
		return;
	}
	
	/* Make ajax call */
	const ajax = $.ajax({
		method: 'POST',
		url: siteUrl + '/account/messages/check-new',
		data: {
			'languageCode': languageCode,
			'oldValue': oldValue,
			'_token': $('input[name=_token]').val()
		}
	});
	ajax.done(function (data) {
		if (typeof data.logged === 'undefined') {
			return;
		}
		
		/* Guest Users - Need to Log In */
		if (data.logged === 0 || data.logged === '0' || data.logged === '') {
			return;
		}
		
		const counterBoxesEl = $('.count-threads-with-new-messages');
		if (!counterBoxesEl.length) {
			return;
		}
		
		/* Logged Users - Notification */
		if (data.countThreadsWithNewMessages > 0) {
			if (data.countThreadsWithNewMessages >= data.countLimit) {
				counterBoxesEl.html(data.countLimit + '+');
			} else {
				counterBoxesEl.html(data.countThreadsWithNewMessages);
			}
			if (data.countThreadsWithNewMessages === 0) {
				counterBoxesEl.hide();
			} else {
				counterBoxesEl.show();
			}
		} else {
			counterBoxesEl.html('0').hide();
		}
	});
}

/**
 * Get the Laravel CSRF Token
 * @param formFieldEl
 * @returns {string|null}
 */
function getCsrfToken(formFieldEl = null) {
	let token = null;
	
	/* Find the token from the _token hidden field */
	const _tokenEl = document.querySelector("input[name=_token]");
	if (_tokenEl) {
		token = _tokenEl.value;
	}
	
	/*
	 * If the token is not found, search it through the form data attribute
	 * Note: The form element can be handled by giving one of its fields
	 */
	if (isEmpty(token)) {
		if (isDomElement(formFieldEl)) {
			const tokenFormEl = formFieldEl.closest('form');
			if (tokenFormEl) {
				token = tokenFormEl.dataset.csrfToken || tokenFormEl.dataset.token || null;
			}
		}
	}
	
	return token;
}
