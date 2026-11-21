/*
 * Listing strength score (public/assets/js/app/listing-form/strength-score.js).
 * Run by tests/Feature/ListingForm/ListingStrengthTest.php with "node --test".
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');

const {score} = require(path.join(__dirname, '../../public/assets/js/app/listing-form/strength-score.js'));

const CONFIG = {titleMin: 2, titleMax: 150, descriptionMin: 5};
const EMPTY = {categoryId: 0, categoryName: '', title: '', description: '', price: '', priceShown: true, negotiable: false, cityId: '', cityName: '', email: '', phone: ''};
const COMPLETE = {
	...EMPTY,
	categoryId: 10,
	categoryName: 'Mobile Phones',
	title: 'Apple iPhone 17 Pro Cosmic Orange',
	description: 'x'.repeat(220),
	price: '999',
	cityId: '42',
	cityName: 'Dhaka',
	email: 'seller@example.test',
};

const item = (result, key) => result.items.find((entry) => entry.key === key);

test('an empty form scores nothing and needs work', () => {
	// Act
	const result = score(EMPTY, CONFIG);

	// Assert
	assert.equal(result.score, 0);
	assert.equal(result.tier, 'weak');
	assert.equal(result.items.length, 6);
});

test('a complete listing scores 100 and is excellent', () => {
	// Act
	const result = score(COMPLETE, CONFIG);

	// Assert
	assert.equal(result.score, 100);
	assert.equal(result.tier, 'excellent');
	assert.equal(result.next, null);
	assert.ok(result.items.every((entry) => entry.done));
});

test('the description is worth the most, so it is the first next step', () => {
	// Act
	const result = score(EMPTY, CONFIG);

	// Assert
	assert.equal(result.next.key, 'description');
	assert.equal(result.next.remaining, 25);
});

test('a short description earns part of its points and says how much is missing', () => {
	// Act
	const result = score({...EMPTY, description: 'x'.repeat(100)}, CONFIG);
	const description = item(result, 'description');

	// Assert
	assert.equal(result.score, 13);
	assert.equal(description.done, false);
	assert.deepEqual(description.hint, {key: 'hint_description_short', params: {count: 100}});
	assert.equal(description.bar, 0.5);
});

test('a title needs 15 to 80 characters to count in full', () => {
	// Act
	const short = item(score({...EMPTY, title: 'iPhone'}, CONFIG), 'title');
	const good = item(score({...EMPTY, title: 'Apple iPhone 17 Pro 256GB'}, CONFIG), 'title');
	const long = item(score({...EMPTY, title: 'x'.repeat(95)}, CONFIG), 'title');

	// Assert
	assert.deepEqual(short.hint, {key: 'hint_title_short', params: {count: 9}});
	assert.equal(short.done, false);
	assert.equal(good.done, true);
	assert.deepEqual(long.hint, {key: 'hint_title_long', params: {max: 80}});
	assert.equal(long.done, false);
	assert.equal(long.warn, true);
});

test('stricter site minimums win over the built-in quality targets', () => {
	// Act
	const result = score({...EMPTY, title: 'x'.repeat(20), description: 'x'.repeat(250)}, {...CONFIG, titleMin: 30, descriptionMin: 300});

	// Assert
	assert.equal(item(result, 'title').done, false);
	assert.equal(item(result, 'description').done, false);
});

test('without a price field the other items share all 100 points', () => {
	// Act
	const result = score({...COMPLETE, priceShown: false, price: ''}, CONFIG);

	// Assert
	assert.equal(result.items.length, 5);
	assert.equal(item(result, 'price'), undefined);
	assert.equal(result.score, 100);
});

test('a listing that only lacks contact details is strong, not excellent', () => {
	// Act
	const result = score({...COMPLETE, email: ''}, CONFIG);

	// Assert
	assert.equal(result.score, 90);
	assert.equal(result.tier, 'strong');
	assert.equal(result.next.key, 'contact');
});

test('done items name what was chosen', () => {
	// Act
	const result = score(COMPLETE, CONFIG);

	// Assert
	assert.deepEqual(item(result, 'category').hint, {text: 'Mobile Phones'});
	assert.deepEqual(item(result, 'city').hint, {text: 'Dhaka'});
});

test('photos only count where the form has them (single-step mode)', () => {
	// Act
	const multiStep = score(COMPLETE, CONFIG);
	const singleStep = score({...COMPLETE, photoCount: 0}, CONFIG);

	// Assert
	assert.equal(item(multiStep, 'photos'), undefined);
	assert.equal(item(singleStep, 'photos').done, false);
	assert.equal(singleStep.items.length, 7);
	assert.equal(singleStep.next.key, 'photos');
	assert.deepEqual(item(singleStep, 'photos').hint, {key: 'hint_photos_empty'});
});

test('three photos make a full gallery; fewer earn part of the points', () => {
	// Act
	const one = item(score({...EMPTY, photoCount: 1}, CONFIG), 'photos');
	const three = score({...COMPLETE, photoCount: 3}, CONFIG);

	// Assert
	assert.deepEqual(one.hint, {key: 'hint_photos_more', params: {count: 2}});
	assert.equal(one.bar, 1 / 3);
	assert.equal(item(three, 'photos').done, true);
	assert.equal(three.score, 100);
	assert.equal(three.tier, 'excellent');
});

test('tiers follow the score', () => {
	// Act
	const fair = score({...EMPTY, description: 'x'.repeat(200), title: 'Apple iPhone 17 Pro 256GB'}, CONFIG);
	const strong = score({...COMPLETE, cityId: '', cityName: '', email: ''}, CONFIG);

	// Assert
	assert.equal(fair.score, 45);
	assert.equal(fair.tier, 'fair');
	assert.equal(strong.score, 75);
	assert.equal(strong.tier, 'strong');
});
