<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
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
				'name'        => 'app',
				'label'       => 'Application',
				'description' => 'Application Global Options',
			],
			[
				'name'        => 'header',
				'label'       => 'Header',
				'description' => 'Pages Header',
			],
			[
				'name'        => 'footer',
				'label'       => 'Footer',
				'description' => 'Pages Footer',
			],
			[
				'name'        => 'style',
				'label'       => 'Style',
				'description' => 'Style Customization',
			],
			[
				'name'        => 'listing_form',
				'label'       => 'Listing Form',
				'description' => 'Listing Form Options',
			],
			[
				'name'        => 'listings_list',
				'label'       => 'Listings List',
				'description' => 'Listings List Options',
			],
			[
				'name'        => 'listing_page',
				'label'       => 'Listing Page',
				'description' => 'Listing Details Page Options',
			],
			[
				'name'        => 'mail',
				'label'       => 'Mail',
				'description' => 'Mail Sending Configuration',
			],
			[
				'name'        => 'sms',
				'label'       => 'SMS',
				'description' => 'SMS Sending Configuration',
			],
			[
				'name'        => 'upload',
				'label'       => 'Upload',
				'description' => 'Upload Settings',
			],
			[
				'name'        => 'localization',
				'label'       => 'Localization',
				'description' => 'Localization Configuration',
			],
			[
				'name'        => 'security',
				'label'       => 'Security',
				'description' => 'Security Options',
			],
			[
				'name'        => 'auth',
				'label'       => 'Authentication',
				'description' => 'Authentication Options',
			],
			[
				'name'        => 'social_auth',
				'label'       => 'Social Authentication',
				'description' => 'Social Network Authentication',
			],
			[
				'name'        => 'social_link',
				'label'       => 'Social Network Links',
				'description' => 'Social Network Profiles',
			],
			[
				'name'        => 'social_share',
				'label'       => 'Social Share',
				'description' => 'Social Media Sharing',
			],
			[
				'name'        => 'optimization',
				'label'       => 'Optimization',
				'description' => 'Optimization Options',
			],
			[
				'name'        => 'seo',
				'label'       => 'SEO',
				'description' => 'SEO Options',
			],
			[
				'name'        => 'pagination',
				'label'       => 'Pagination',
				'description' => 'Pagination & Limit Options',
			],
			[
				'name'        => 'other',
				'label'       => 'Others',
				'description' => 'Other Options',
			],
			[
				'name'        => 'cron',
				'label'       => 'Cron',
				'description' => 'Cron Job Options',
			],
			[
				'name'        => 'backup',
				'label'       => 'Backup',
				'description' => 'Backup Configuration',
			],
		];
		
		// Add or update columns
		$timezone = config('app.timezone', 'UTC');
		$entries = collect($entries)
			->map(function ($item) use ($timezone) {
				$item['fields'] = null;
				$item['field_values'] = null;
				
				$item['parent_id'] = null;
				$item['lft'] = 0;
				$item['rgt'] = 0;
				$item['depth'] = 0;
				
				$item['active'] = 1;
				$item['created_at'] = now($timezone)->format('Y-m-d H:i:s');
				$item['updated_at'] = null;
				
				return $item;
			})->toArray();
		
		$tableName = (new Setting())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
}
