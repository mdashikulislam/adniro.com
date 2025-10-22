<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
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
				'name'        => 'search_form',
				'label'       => 'Search Form Area',
				'description' => 'Search Form Area Section',
			],
			[
				'name'        => 'categories',
				'label'       => 'Categories',
				'description' => 'Categories Section',
			],
			[
				'name'        => 'premium_listings',
				'label'       => 'Premium Listings',
				'description' => 'Premium Listings Section',
			],
			[
				'name'        => 'locations',
				'label'       => 'Locations & SVG Map',
				'description' => 'Locations & Country\'s SVG Map Section',
			],
			[
				'name'        => 'latest_listings',
				'label'       => 'Latest Listings',
				'description' => 'Latest Listings Section',
			],
			[
				'name'        => 'stats',
				'label'       => 'Mini Stats',
				'description' => 'Mini Stats Section',
			],
			[
				'name'        => 'text_area',
				'label'       => 'Text Area',
				'description' => 'Text Area Section',
				'active'      => 0,
			],
			[
				'name'        => 'top_ad',
				'label'       => 'Advertising #1',
				'description' => 'Advertising #1 Section',
				'active'      => 0,
			],
			[
				'name'        => 'bottom_ad',
				'label'       => 'Advertising #2',
				'description' => 'Advertising #2 Section',
				'active'      => 0,
			],
		];
		
		// Add or update columns
		$timezone = config('app.timezone', 'UTC');
		$entries = collect($entries)
			->map(function ($item) use ($timezone) {
				$item['belongs_to'] = 'home';
				$item['fields'] = null;
				$item['field_values'] = null;
				
				$item['parent_id'] = null;
				$item['lft'] = 0;
				$item['rgt'] = 0;
				$item['depth'] = 0;
				
				$item['active'] = array_key_exists('active', $item) ? $item['active'] : 1;
				$item['created_at'] = now($timezone)->format('Y-m-d H:i:s');
				$item['updated_at'] = null;
				
				return $item;
			})->toArray();
		
		$tableName = (new Section())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
}
