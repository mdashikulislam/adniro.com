<?php

use App\Enums\ThemePreference;
use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ===| FILES |===
try {
	
	// Directories
	File::deleteDirectory(public_path('assets/bootstrap/5.0.0/'));
	File::deleteDirectory(public_path('assets/bootstrap/5.2.2/'));
	File::deleteDirectory(public_path('assets/bootstrap/5.3.6/'));
	File::deleteDirectory(public_path('assets/css/rtl/'));
	File::deleteDirectory(public_path('assets/plugins/owlcarousel/'));
	File::deleteDirectory(resource_path('views/vendor/pagination/api/ajax/'));
	
	// Files
	File::delete(public_path('assets/auth/css/style.rtl.css'));
	File::delete(public_path('assets/resources/css/admin.css'));
	File::delete(public_path('assets/resources/css/app.css'));
	File::delete(public_path('assets/resources/css/app.rtl.css'));
	File::delete(public_path('assets/resources/css/auth.css'));
	File::delete(public_path('assets/resources/css/auth.rtl.css'));
	
	File::delete(resource_path('sass/_variables.scss'));
	File::delete(resource_path('sass/admin.scss'));
	File::delete(resource_path('sass/app.rtl.scss'));
	File::delete(resource_path('sass/app.scss'));
	File::delete(resource_path('sass/auth.rtl.scss'));
	File::delete(resource_path('sass/auth.scss'));
	
	File::delete(resource_path('views/front/common/css/dark.blade.php'));
	
	File::delete(resource_path('views/vendor/pagination/api/bootstrap-4.blade.php'));
	File::delete(resource_path('views/vendor/pagination/api/default.blade.php'));
	File::delete(resource_path('views/vendor/pagination/api/meta.blade.php'));
	File::delete(resource_path('views/vendor/pagination/api/simple-bootstrap-4.blade.php'));
	File::delete(resource_path('views/vendor/pagination/api/simple-default.blade.php'));
	File::delete(resource_path('views/vendor/pagination/bootstrap-4.blade.php'));
	File::delete(resource_path('views/vendor/pagination/default.blade.php'));
	File::delete(resource_path('views/vendor/pagination/semantic-ui.blade.php'));
	File::delete(resource_path('views/vendor/pagination/simple-bootstrap-4.blade.php'));
	File::delete(resource_path('views/vendor/pagination/simple-default.blade.php'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	// ...
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
