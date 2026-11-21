<?php

namespace Tests\Feature\ListingForm;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Safety net for the listing form redesign (adniro): every create and edit page renders in both
 * form modes (single-step and multi-step, switchable by the admin at any time), and keeps the
 * field names the server reads and the element IDs the form scripts and the AI box rely on
 * (d.modal.category.js, d.modal.location.js, package-payment.js, ai.formbox.js).
 *
 * Field names must match exactly: a missing or extra name changes what is saved. IDs are checked
 * as "still there", so the redesign may add new ones.
 */

class ListingFormPagesTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const MULTI = 'multi-steps-form';
	private const SINGLE = 'single-step-form';
	private const OWNER_ID = 50;
	private const OTHER_MEMBER_ID = 51;

	/** Fields every details form sends */
	private const DETAILS_FIELDS = [
		'_token', 'category_id', 'category_type', 'title', 'description', 'price', 'negotiable', 'country_code',
		'selected_admin_type', 'selected_admin_code', 'selected_city_id', 'selected_city_name', 'city_id', 'tags[]',
		'is_permanent', 'contact_name', 'auth_field', 'email', 'phone', 'phone_hidden', 'phone_country',
	];
	private const GUEST_FIELDS = ['accept_terms', 'accept_marketing_offers'];

	/** Element IDs the category, location and price scripts use */
	private const DETAILS_HOOKS = [
		'catsContainer', 'categoryId', 'categoryType', 'cfContainer', 'priceBloc', 'price', 'negotiable', 'countryCode',
		'selectedAdminType', 'selectedAdminCode', 'selectedCityId', 'selectedCityName', 'cityBox', 'cityId',
		'isPermanentBox', 'isPermanent', 'authField-email', 'phone', 'phoneHidden',
	];
	private const PHOTO_HOOKS = ['dz_pictures', 'uploadError', 'uploadSuccess'];

	private int $postId;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		// Guests may post and listings take 5 photos, as on the live site
		config([
			'settings.listing_form.guest_can_submit_listings' => '1',
			'settings.listing_form.pictures_limit'            => 5,
		]);
		$this->stockMember(self::OWNER_ID);
		$this->postId = $this->stockListing('Grey corner sofa', ['user_id' => self::OWNER_ID])->id;
	}

	public static function formPages(): array
	{
		$details = self::DETAILS_FIELDS;
		$guest = array_merge($details, self::GUEST_FIELDS);
		$edit = array_merge($details, ['_method', 'post_id']);

		return [
			'multi-step create, guest'    => [self::MULTI, false, '/posts/create', $guest, [...self::DETAILS_HOOKS, 'acceptTerms', 'acceptMarketingOffers', 'nextStepBtn']],
			'multi-step create, member'   => [self::MULTI, true, '/posts/create', $details, [...self::DETAILS_HOOKS, 'contactName', 'nextStepBtn']],
			'multi-step create photos'    => [self::MULTI, true, '/posts/create/photos', ['_token', 'pictures[]', 'actionButton'], [...self::PHOTO_HOOKS, 'nextStepBtn']],
			'multi-step edit details'     => [self::MULTI, true, '/posts/{id}/details', $edit, [...self::DETAILS_HOOKS, 'nextStepBtn']],
			'multi-step edit photos'      => [self::MULTI, true, '/posts/{id}/photos', ['_token', 'post_id', 'pictures[]'], [...self::PHOTO_HOOKS, 'nextStepAction']],
			'single-step create, guest'   => [self::SINGLE, false, '/create', [...$guest, 'pictures[]'], [...self::DETAILS_HOOKS, 'acceptTerms', 'acceptMarketingOffers', 'payableFormSubmitButton']],
			'single-step create, member'  => [self::SINGLE, true, '/create', [...$details, 'pictures[]'], [...self::DETAILS_HOOKS, 'contactName', 'payableFormSubmitButton']],
			'single-step edit'            => [self::SINGLE, true, '/edit/{id}', [...$edit, 'payable_id', 'pictures[]'], [...self::DETAILS_HOOKS, 'payableFormSubmitButton']],
		];
	}

	#[DataProvider('formPages')]
	public function test_each_form_page_keeps_its_fields_and_script_hooks(string $mode, bool $asOwner, string $url, array $fields, array $hooks): void
	{
		// Arrange: the photos step opens once the details step is in the session
		config(['settings.listing_form.publication_form_type' => $mode]);
		$request = $asOwner ? $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID)) : $this;

		// Act
		$response = $request->withSession(['postInput' => ['title' => 'Grey corner sofa']])
			->get(str_replace('{id}', (string)$this->postId, $url));

		// Assert
		$response->assertOk();
		[$names, $ids] = $this->formContract($response);
		$this->assertEqualsCanonicalizing($fields, $names, 'the form sends exactly these fields');
		$this->assertEmpty(array_diff($hooks, $ids), 'missing element IDs: ' . implode(', ', array_diff($hooks, $ids)));
	}

	public static function detailsPages(): array
	{
		return [
			'multi-step create, guest'  => [self::MULTI, false, '/posts/create', ['title', 'description', 'price', 'cityId', 'contactName', 'email', 'phone']],
			'multi-step edit details'   => [self::MULTI, true, '/posts/{id}/details', ['title', 'description', 'price', 'cityId', 'contactName', 'email', 'phone']],
			'single-step create, member'=> [self::SINGLE, true, '/create', ['title', 'description', 'price', 'cityId', 'email', 'phone']],
			'single-step edit'          => [self::SINGLE, true, '/edit/{id}', ['title', 'description', 'price', 'cityId', 'contactName', 'email', 'phone']],
		];
	}

	#[DataProvider('detailsPages')]
	public function test_labels_point_at_their_fields(string $mode, bool $asOwner, string $url, array $fields): void
	{
		// Arrange
		config(['settings.listing_form.publication_form_type' => $mode]);
		$request = $asOwner ? $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID)) : $this;

		// Act
		$xpath = $this->formXPath($request->get(str_replace('{id}', (string)$this->postId, $url)));

		// Assert: each label's "for" names an element on the page, and the required text fields say so
		foreach ($fields as $field) {
			$label = $xpath->query("//form[@id='payableForm']//label[@for][starts-with(@for, '{$field}') or contains(@for, '_{$field}')]")->item(0);
			$this->assertNotNull($label, "a label for {$field}");
			$for = $label->getAttribute('for');
			$this->assertSame(1, $xpath->query("//*[@id='{$for}']")->length, "exactly one element with id {$for}");
		}
		$this->assertSame('true', $xpath->query("//input[@name='title']")->item(0)?->getAttribute('aria-required'));
	}

	public static function editPages(): array
	{
		return [
			'multi-step edit details' => [self::MULTI, '/posts/{id}/details'],
			'single-step edit'        => [self::SINGLE, '/edit/{id}'],
		];
	}

	#[DataProvider('editPages')]
	public function test_an_edit_page_shows_the_saved_listing(string $mode, string $url): void
	{
		// Arrange
		config(['settings.listing_form.publication_form_type' => $mode]);

		// Act
		$response = $this->actingAs(\App\Models\User::query()->find(self::OWNER_ID))
			->get(str_replace('{id}', (string)$this->postId, $url));

		// Assert
		$xpath = $this->formXPath($response);
		$this->assertSame('Grey corner sofa', $xpath->query("//input[@name='title']")->item(0)?->getAttribute('value'));
		$this->assertSame('150', $xpath->query("//input[@name='price']")->item(0)?->getAttribute('value'));
		$this->assertSame('Sam', $xpath->query("//input[@name='contact_name']")->item(0)?->getAttribute('value'));
		$this->assertSame((string)$this->postId, $xpath->query("//input[@name='post_id']")->item(0)?->getAttribute('value'));
		$this->assertSame('Springfield', $xpath->query("//input[@name='selected_city_name']")->item(0)?->getAttribute('value'));
	}

	public function test_the_multi_step_session_fills_the_create_page(): void
	{
		// Arrange
		config(['settings.listing_form.publication_form_type' => self::MULTI]);

		// Act
		$response = $this->withSession(['postInput' => ['title' => 'Oak table', 'price' => '80', 'contact_name' => 'Ana']])
			->get('/posts/create');

		// Assert
		$xpath = $this->formXPath($response);
		$this->assertSame('Oak table', $xpath->query("//input[@name='title']")->item(0)?->getAttribute('value'));
		$this->assertSame('80', $xpath->query("//input[@name='price']")->item(0)?->getAttribute('value'));
		$this->assertSame('Ana', $xpath->query("//input[@name='contact_name']")->item(0)?->getAttribute('value'));
	}

	public static function inactiveModeUrls(): array
	{
		return [
			'single-step URL while multi-step is on' => [self::MULTI, '/create', '/posts/create'],
			'multi-step URL while single-step is on' => [self::SINGLE, '/posts/create', '/create'],
		];
	}

	#[DataProvider('inactiveModeUrls')]
	public function test_the_inactive_mode_sends_people_to_the_active_one(string $mode, string $url, string $target): void
	{
		// Arrange
		config(['settings.listing_form.publication_form_type' => $mode]);

		// Act
		$response = $this->get($url);

		// Assert
		$response->assertRedirect();
		$this->assertStringEndsWith($target, parse_url((string)$response->headers->get('Location'), PHP_URL_PATH));
	}

	public static function editUrls(): array
	{
		return [
			'multi-step details' => [self::MULTI, '/posts/{id}/details'],
			'multi-step photos'  => [self::MULTI, '/posts/{id}/photos'],
			'single-step edit'   => [self::SINGLE, '/edit/{id}'],
		];
	}

	#[DataProvider('editUrls')]
	public function test_a_member_cannot_open_someone_elses_edit_page(string $mode, string $url): void
	{
		// Arrange
		config(['settings.listing_form.publication_form_type' => $mode]);
		$otherMember = $this->stockMember(self::OTHER_MEMBER_ID);

		// Act
		$response = $this->actingAs($otherMember)->get(str_replace('{id}', (string)$this->postId, $url));

		// Assert
		$response->assertNotFound();
	}

	#[DataProvider('editUrls')]
	public function test_a_guest_is_sent_to_log_in_from_an_edit_page(string $mode, string $url): void
	{
		// Arrange
		config(['settings.listing_form.publication_form_type' => $mode]);

		// Act
		$response = $this->get(str_replace('{id}', (string)$this->postId, $url));

		// Assert
		$response->assertRedirect();
		$this->assertStringNotContainsString('payableForm', (string)$response->getContent());
	}

	/**
	 * The field names and element IDs inside the listing form.
	 *
	 * @return array{0: string[], 1: string[]}
	 */
	private function formContract(TestResponse $response): array
	{
		$xpath = $this->formXPath($response);
		$form = $xpath->query('//form[@id="payableForm"]')->item(0);
		$this->assertNotNull($form, 'the page has the listing form');

		$names = [];
		foreach ($xpath->query('.//*[@name]', $form) as $node) {
			$names[] = $node->getAttribute('name');
		}
		$ids = [];
		foreach ($xpath->query('.//*[@id]', $form) as $node) {
			$ids[] = $node->getAttribute('id');
		}

		return [array_values(array_unique($names)), array_values(array_unique($ids))];
	}

	private function formXPath(TestResponse $response): DOMXPath
	{
		$response->assertOk();
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML((string)$response->getContent());
		libxml_clear_errors();

		return new DOMXPath($dom);
	}
}
