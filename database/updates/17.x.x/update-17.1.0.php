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
	File::deleteDirectory(app_path('Helpers/Common/DBTool/'));
	File::deleteDirectory(public_path('assets/plugins/bootstrap-daterangepicker/'));
	File::deleteDirectory(public_path('assets/plugins/bootstrap-datetimepicker/'));
	// File::deleteDirectory(public_path('assets/plugins/select2-bootstrap-theme/0.1.0-beta.10/'));
	File::deleteDirectory(public_path('assets/plugins/select2-bootstrap-theme/'));
	File::deleteDirectory(public_path('public/assets/plugins/sweetalert2/11.1.10/'));
	File::deleteDirectory(public_path('public/assets/plugins/intl-tel-input/17.0.18/'));
	File::deleteDirectory(public_path('assets/plugins/jquery-hideMaxListItems/'));
	
	File::deleteDirectory(resource_path('views/front/layouts/inc/tools/captcha/'));
	File::deleteDirectory(resource_path('views/front/layouts/inc/tools/wysiwyg/'));
	File::deleteDirectory(resource_path('views/admin/layouts/inc/'));
	File::deleteDirectory(resource_path('views/front/layouts/inc/'));
	File::deleteDirectory(resource_path('views/setup/install/helpers/'));
	
	
	// Files
	File::delete(app_path('Helpers/Common/DBTool.php'));
	File::delete(app_path('Http/Controllers/Web/Admin/FileController.php'));
	File::delete(app_path('Http/Controllers/Web/Front/Traits/HasIntlTelInput.php'));
	File::delete(app_path('Services/User/Update/DarkMode.php'));
	File::delete(resource_path('views/front/post/createOrEdit/multiSteps/inc/photos-alert-js.blade.php'));
	File::delete(public_path('assets/js/helpers/http.request.js'));
	File::delete(public_path('assets/js/helpers/UrlQuery.js'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	// Add theme column if it doesn't exist
	if (
		Schema::hasTable('users') &&
		!Schema::hasColumn('users', 'theme_preference')
	) {
		Schema::table('users', function (Blueprint $table) {
			$themes = implode(', ', ThemePreference::values());
			
			$table->string('theme_preference')->nullable()
				->comment("User theme preference: $themes")
				->after('dark_mode');
		});
	}
	
	// Update existing records if both columns exist
	if (
		Schema::hasTable('users') &&
		Schema::hasColumn('users', 'dark_mode') &&
		Schema::hasColumn('users', 'theme_preference')
	) {
		DB::table('users')->where('dark_mode', true)->update(['theme_preference' => 'dark']);
	}
	
	// Remove dark_mode column if it exists
	if (
		Schema::hasTable('users') &&
		Schema::hasColumn('users', 'dark_mode')
	) {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('dark_mode');
		});
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
