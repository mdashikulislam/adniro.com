/*
 * adniro: one-step undo for text the AI put into a field (redesigned listing form).
 * Keeps the seller's own value, exactly as the field or editor held it. Undo only works while
 * the field still holds the AI text, so it can never wipe edits the seller made afterwards.
 */
(function (window) {
	'use strict';
	
	const LF = window.adniroListingForm;
	if (!LF) {
		return;
	}
	
	/** @type {Object<string, {before: string, applied: string}>} */
	const saved = {};
	
	LF.undo = {
		/** Put AI text into a field, remembering what was there */
		apply(name, text) {
			const before = LF.rawValue(name);
			LF.setText(name, text);
			saved[name] = {before: before, applied: LF.rawValue(name)};
		},
		/** True while the field still holds exactly the AI text */
		canRestore(name) {
			return !!saved[name] && LF.rawValue(name) === saved[name].applied;
		},
		/** Put the seller's value back; false when there is nothing to undo or they edited since */
		restore(name) {
			if (!this.canRestore(name)) {
				this.forget(name);
				return false;
			}
			LF.setRawValue(name, saved[name].before);
			this.forget(name);
			return true;
		},
		forget(name) {
			delete saved[name];
		},
	};
})(window);
