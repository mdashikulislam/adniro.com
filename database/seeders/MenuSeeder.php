<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
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
				'name'        => 'Header Menu',
				'location'    => 'header',
				'description' => 'Header Menu',
			],
			[
				'name'        => 'Footer Menu',
				'location'    => 'footer',
				'description' => 'Footer Menu',
			],
		];
		
		// Add or update columns
		$timezone = config('app.timezone', 'UTC');
		$entries = collect($entries)
			->map(function ($item) use ($timezone) {
				$item['parent_id'] = null;
				$item['lft'] = 0;
				$item['rgt'] = 0;
				$item['depth'] = 0;
				
				$item['active'] = 1;
				$item['created_at'] = now($timezone)->format('Y-m-d H:i:s');
				$item['updated_at'] = null;
				
				return $item;
			})->toArray();
		
		$tableName = (new Menu())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
}
