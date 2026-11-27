<?php

namespace Tests\Feature\ListingForm;

use App\Models\CategoryField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * No field of the listing form's "Additional Details" section is required (adniro): the migration switches
 * "Required" off on every listing field. The switch itself stays in Admin > Custom fields.
 */

class OptionalDetailsFieldsTest extends TestCase
{
	use RefreshDatabase;
	
	private const MIGRATION = 'migrations/2026_11_27_000200_make_the_listing_details_fields_optional.php';
	
	protected function setUp(): void
	{
		parent::setUp();
		DB::table('fields')->insert([
			$this->field(10, 'Brand', 'post', 1),
			$this->field(11, 'Storage', 'post', 0),
			$this->field(12, 'Company number', 'user', 1),
		]);
	}
	
	public function test_every_listing_field_becomes_optional(): void
	{
		// Act
		(require database_path(self::MIGRATION))->up();
		
		// Assert
		$this->assertSame(0, DB::table('fields')->where('belongs_to', 'post')->where('required', 1)->count());
	}
	
	public function test_fields_that_do_not_belong_to_listings_are_left_alone(): void
	{
		// Act
		(require database_path(self::MIGRATION))->up();
		
		// Assert
		$this->assertSame(1, (int)DB::table('fields')->where('id', 12)->value('required'));
	}
	
	public function test_the_form_gets_the_change_at_once(): void
	{
		// Arrange: the form has the category's fields in its cache
		DB::table('categories')->insert(['id' => 1, 'parent_id' => null, 'name' => json_encode(['en' => 'Phones']), 'slug' => 'phones', 'lft' => 1, 'rgt' => 2, 'depth' => 0, 'type' => 'classified', 'active' => 1]);
		DB::table('category_field')->insert(['category_id' => 1, 'field_id' => 10, 'disabled_in_subcategories' => 0, 'lft' => 1, 'rgt' => 2]);
		$this->assertSame(1, (int)CategoryField::getFields(1)->firstWhere('id', 10)->required);
		
		// Act
		(require database_path(self::MIGRATION))->up();
		
		// Assert
		$this->assertSame(0, (int)CategoryField::getFields(1)->firstWhere('id', 10)->required);
	}
	
	private function field(int $id, string $name, string $belongsTo, int $required): array
	{
		return ['id' => $id, 'belongs_to' => $belongsTo, 'name' => json_encode(['en' => $name]), 'type' => 'text', 'required' => $required, 'active' => 1];
	}
}
