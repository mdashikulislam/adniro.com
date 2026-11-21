/*
 * adniro service worker: makes the site installable as an app and shows a friendly page when there is no network.
 *
 * It keeps ONE page only, the offline page (/offline, WebAppController). Every other page, listing, account
 * and payment page is always loaded live from the network, so nothing shown is ever out of date.
 * Private and form pages are never touched at all. The site registers it with ?v=<file time>, so a new
 * version of this file replaces the old worker; change VERSION to refresh the kept offline page.
 */
const VERSION = 'v1';
const CACHE_PREFIX = 'adniro-offline-';
const CACHE = CACHE_PREFIX + VERSION;
const OFFLINE_URL = '/offline';

/* Never handled: the admin, accounts, sign-in, the listing form, payments, the API and feeds */
const PRIVATE_PATHS = /^\/(admin|account|auth|posts\/create|posts\/\d+\/(details|photos|payment|finish)|create|edit|payment|api|ajax|feed|sitemap|logout)/i;

self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(CACHE)
			.then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
			.then(() => self.skipWaiting())
	);
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys()
			.then((keys) => Promise.all(keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE).map((key) => caches.delete(key))))
			.then(() => self.clients.claim())
	);
});

self.addEventListener('fetch', (event) => {
	const request = event.request;
	if (request.mode !== 'navigate' || request.method !== 'GET') {
		return;
	}

	const url = new URL(request.url);
	if (url.origin !== self.location.origin || PRIVATE_PATHS.test(url.pathname)) {
		return;
	}

	// Live from the network; the offline page only when the network fails
	event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
});
