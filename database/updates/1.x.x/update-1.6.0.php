<?php

use App\Exceptions\Custom\CustomException;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

// ===| DATABASE |===
try {
	
	// settings
	$settingTable = (new Setting())->getTable();
	if (Schema::hasColumn($settingTable, 'key')) {
		DB::table('settings')->where('field', '')->update(['field' => '{"name":"value","label":"Value","type":"text"}']);
		DB::table('settings')->where('key', 'ads_pictures_number')->update(['lft' => '14', 'rgt' => '15', 'depth' => '1']);
		DB::table('settings')->where('key', 'custom_css')->update(['lft' => '124', 'rgt' => '125', 'depth' => '1']);
		DB::table('settings')->where('key', 'show_ad_on_googlemap')->update(['lft' => '22', 'rgt' => '23', 'depth' => '1']);
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
