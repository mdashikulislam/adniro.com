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
	// ...
	
	// Files
	File::delete(app_path('Observers/Traits/PaymentTrait.php'));
	
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	//...
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
