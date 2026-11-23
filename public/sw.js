/*
 * adniro: the installable app has been removed. Phones that installed it still have the old service worker,
 * which checks this file for updates. This version takes over, deletes the kept offline page and unregisters
 * itself, so the site goes back to a plain website. It handles no requests and reloads nothing.
 * Keep this file for a few months, then delete it.
 */
const CACHE_PREFIX = 'adniro-offline-';

self.addEventListener('install', () => {
	self.skipWaiting();
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys()
			.then((keys) => Promise.all(keys.filter((key) => key.startsWith(CACHE_PREFIX)).map((key) => caches.delete(key))))
			.then(() => self.registration.unregister())
	);
});
