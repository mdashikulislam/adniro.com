/*
 * adniro: a question before a risky message is sent (assets/js/app/chat.js): one with a phone number or an
 * email address, or wording about paying outside the site. The member can send it anyway; nothing is blocked.
 * Asked once per conversation and kind, so it does not nag. The patterns are the server's own
 * (App\Services\Chat\ChatSafety), so the reader's "be careful" line and this question agree.
 */
(function (window) {
	'use strict';

	/** @param {{T: Object, store: Object}} ctx @param {Array<{kind: string, source: string, flags: string}>} patterns */
	window.AdnChatGuard = function (ctx, patterns) {
		var T = ctx.T;
		var rules = (patterns || []).map(function (pattern) {
			try {
				return {kind: pattern.kind, test: new RegExp(pattern.source, pattern.flags)};
			} catch (error) {
				return null; // a pattern this browser cannot read is skipped
			}
		}).filter(Boolean);

		function kindOf(text) {
			var hit = rules.filter(function (rule) {
				return rule.test.test(text);
			})[0];
			return hit ? hit.kind : null;
		}

		/** Resolves true when the message may be sent. */
		function confirm(text) {
			var open = ctx.store.open;
			var kind = text ? kindOf(text) : null;
			if (!kind || !open || typeof window.Swal === 'undefined') {
				return Promise.resolve(true);
			}
			open.warned = open.warned || {};
			if (open.warned[kind]) {
				return Promise.resolve(true);
			}

			return window.Swal.fire({
				icon: 'warning',
				title: T['guard_' + kind + '_title'],
				text: T['guard_' + kind + '_text'],
				showCancelButton: true,
				confirmButtonText: T.guard_send,
				cancelButtonText: T.guard_edit,
				reverseButtons: true,
				focusCancel: true
			}).then(function (result) {
				if (result.isConfirmed) {
					open.warned[kind] = true;
				}
				return result.isConfirmed;
			});
		}

		return {confirm: confirm};
	};
})(window);
