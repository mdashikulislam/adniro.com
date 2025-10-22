<?php

use App\Exceptions\Custom\CustomException;
use App\Helpers\Common\JsonUtils;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ===| FILES |===
try {
	
	// Directories
	File::deleteDirectory(app_path('Http/Controllers/Web/Admin/Panel/Traits/'));
	File::deleteDirectory(app_path('Models/Section/Traits/'));
	File::deleteDirectory(config_path('prologue/'));
	File::deleteDirectory(resource_path('views/vendor/flash/'));
	
	// Files
	File::delete(app_path('Helpers/Common/UrlQuery.php'));
	File::delete(public_path('assets/js/helpers/uri.js'));
	File::delete(resource_path('views/admin/layouts/partials/alerts.blade.php'));
	
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	//...
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
