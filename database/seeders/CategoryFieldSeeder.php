<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\CategoryField;
use Illuminate\Database\Seeder;

class CategoryFieldSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$entries = [
			[
				'category_id' => '1',
				'field_id'    => '1',
			],
			[
				'category_id' => '1',
				'field_id'    => '2',
			],
			[
				'category_id' => '1',
				'field_id'    => '3',
			],
			[
				'category_id' => '1',
				'field_id'    => '4',
			],
			[
				'category_id' => '1',
				'field_id'    => '5',
			],
			[
				'category_id' => '1',
				'field_id'    => '6',
			],
			[
				'category_id' => '1',
				'field_id'    => '7',
			],
			[
				'category_id' => '1',
				'field_id'    => '8',
			],
			[
				'category_id' => '9',
				'field_id'    => '14',
			],
			[
				'category_id' => '9',
				'field_id'    => '15',
			],
			
			[
				'category_id' => '14',
				'field_id'    => '16',
			],
			[
				'category_id' => '14',
				'field_id'    => '17',
			],
			[
				'category_id' => '30',
				'field_id'    => '8',
			],
			[
				'category_id' => '37',
				'field_id'    => '9',
			],
			[
				'category_id' => '37',
				'field_id'    => '10',
			],
			[
				'category_id' => '37',
				'field_id'    => '11',
			],
			[
				'category_id' => '37',
				'field_id'    => '12',
			],
			[
				'category_id' => '37',
				'field_id'    => '13',
			],
			[
				'category_id' => '54',
				'field_id'    => '8',
			],
			[
				'category_id' => '73',
				'field_id'    => '18',
			],
			[
				'category_id' => '73',
				'field_id'    => '19',
			],
			[
				'category_id' => '73',
				'field_id'    => '20',
			],
			[
				'category_id' => '122',
				'field_id'    => '21',
			],
			[
				'category_id' => '122',
				'field_id'    => '22',
			],
			[
				'category_id' => '122',
				'field_id'    => '23',
			],
		];
		
		// Add or update columns
		$entries = collect($entries)
			->map(function ($item) {
				$item['disabled_in_subcategories'] = '0';
				
				$item['parent_id'] = null;
				$item['lft'] = 0;
				$item['rgt'] = 0;
				$item['depth'] = 0;
				
				return $item;
			})->toArray();
		
		$tableName = (new CategoryField())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
}
