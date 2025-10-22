<?php

use App\Exceptions\Custom\CustomException;
use App\Helpers\Common\DBUtils\DBEncoding;
use App\Helpers\Common\DotenvEditor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ===| FILES |===
try {
	
	// Helpers/Common/Categories
	File::deleteDirectory(app_path('Helpers/Common/Categories/'));
	
	// JS Helpers
	File::delete(public_path('assets/js/global.js'));
	File::delete(public_path('assets/js/http.request.js'));
	File::delete(public_path('assets/js/vanilla.js'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	//...
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
