/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

/**
 * Generic string interpolator.
 *
 * Usage:
 * ------
 * Example #1:
 * const render = createInterpolator('Hello {name}');
 * render({name: 'Mayeul'});
 *
 * Example #1:
 * const cityPathTmpl = 'browsing/countries/{countryCode}/admins/{adminType}/{adminCode}/cities';
 *
 * Compile (this is fast and done only once)
 * const makeCityPath = createInterpolator(cityPathTmpl, 'component');
 *
 * Later in the same script block or in another <script> (after this one):
 * const url = makeCityPath({
 *             countryCode: 'US',
 *             adminType:   '1',
 *             adminCode:   'CO'
 *         });
 * console.log(url);
 * // browsing/countries/US/admins/1/CO/cities
 *
 * @param template
 * @param encode
 * @returns {function(*): *}
 */
function createInterpolator(template, encode = 'none') {
	const encoder = (encode === 'none')
		? v => String(v)
		: (
			(encode === 'component')
				? encodeURIComponent
				: (encode === 'uri' ? encodeURI : encode)
		);
	
	const keys    = [...template.matchAll(/{(\w+)}/g)].map(m => m[1]);
	const matcher = new RegExp(
		'{(' + keys.map(k => k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')}',
		'g'
	);
	
	return params =>
		template.replace(matcher, (_, key) => {
			if (!(key in params)) {
				throw new Error(`Missing value for placeholder "${key}"`);
			}
			return encoder(params[key], key);
		});
}
