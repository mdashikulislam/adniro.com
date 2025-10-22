<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
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
				'name'              => 'paypal',
				'display_name'      => 'PayPal',
				'description'       => 'Payment with PayPal',
				'has_ccbox'         => 0,
				'is_compatible_api' => 0,
				'countries'         => null,
			],
		];
		
		// Add or update columns
		$entries = collect($entries)
			->map(function ($item) {
				$item['parent_id'] = null;
				$item['lft'] = 0;
				$item['rgt'] = 0;
				$item['depth'] = 0;
				
				$item['active'] = 1;
				
				return $item;
			})->toArray();
		
		$tableName = (new PaymentMethod())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
}
