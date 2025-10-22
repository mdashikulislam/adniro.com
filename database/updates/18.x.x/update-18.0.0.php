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
	File::deleteDirectory(resource_path('views/auth/login/inc/'));
	File::deleteDirectory(resource_path('views/front/post/inc/'));
	File::deleteDirectory(resource_path('views/front/post/show/inc/'));
	File::deleteDirectory(resource_path('views/front/post/createOrEdit/inc/'));
	File::deleteDirectory(resource_path('views/front/post/createOrEdit/multiSteps/inc/'));
	File::deleteDirectory(resource_path('views/front/post/createOrEdit/singleStep/inc/'));
	File::deleteDirectory(resource_path('views/front/search/inc/'));
	File::deleteDirectory(resource_path('views/front/errors/layouts/inc/'));
	File::deleteDirectory(resource_path('views/setup/install/layouts/inc/'));
	File::deleteDirectory(resource_path('views/front/layouts/partials/menu/'));
	File::deleteDirectory(resource_path('views/front/errors/'));
	File::deleteDirectory(public_path('assets/css/icheck/'));
	File::deleteDirectory(public_path('assets/plugins/pace/'));
	
	
	// Files
	File::delete(app_path('Observers/Traits/PictureTrait.php'));
	
	File::delete(public_path('assets/css/animate.min.css'));
	File::delete(public_path('assets/css/bootstrap-select.css'));
	File::delete(public_path('assets/css/bootstrap-select.css.map'));
	File::delete(public_path('assets/css/bootstrap-select.min.css'));
	File::delete(public_path('assets/css/coming-soon.css'));
	File::delete(public_path('assets/css/doc.css'));
	File::delete(public_path('assets/css/doc.css.map'));
	File::delete(public_path('assets/css/fileinput.css'));
	File::delete(public_path('assets/css/fileinput.min.css'));
	File::delete(public_path('assets/css/footable.paginate.css'));
	File::delete(public_path('assets/css/footable.sortable-0.1.css'));
	File::delete(public_path('assets/css/footable-0.1.css'));
	File::delete(public_path('assets/css/style-main.css'));
	File::delete(public_path('assets/css/wizard.css'));
	
	File::delete(public_path('assets/css/rtl/animate.min.css'));
	File::delete(public_path('assets/css/rtl/bootstrap-select.css'));
	File::delete(public_path('assets/css/rtl/bootstrap-select.min.css'));
	File::delete(public_path('assets/css/rtl/coming-soon.css'));
	File::delete(public_path('assets/css/rtl/fileinput.css'));
	File::delete(public_path('assets/css/rtl/fileinput.min.css'));
	File::delete(public_path('assets/css/rtl/font-awesome.css'));
	File::delete(public_path('assets/css/rtl/font-awesome.min.css'));
	File::delete(public_path('assets/css/rtl/fontello.css'));
	File::delete(public_path('assets/css/rtl/fontello-codes.css'));
	File::delete(public_path('assets/css/rtl/fontello-embedded.css'));
	File::delete(public_path('assets/css/rtl/fontello-ie7.css'));
	File::delete(public_path('assets/css/rtl/fontello-ie7-codes.css'));
	File::delete(public_path('assets/css/rtl/footable.paginate.css'));
	File::delete(public_path('assets/css/rtl/footable.sortable-0.1.css'));
	File::delete(public_path('assets/css/rtl/footable-0.1.css'));
	File::delete(public_path('assets/css/rtl/rtlfont.css'));
	File::delete(public_path('assets/css/rtl/style-main.css'));
	File::delete(public_path('assets/css/rtl/wizard.css'));
	
	File::delete(resource_path('views/front/common/css/ribbons.blade.php'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	// packages
	$tableName = 'packages';
	if (Schema::hasTable($tableName)) {
		// packages.ribbon
		if (Schema::hasColumn($tableName, 'ribbon')) {
			Schema::table($tableName, function (Blueprint $table) use ($tableName) {
				$table->string('ribbon', 191)->nullable()
					->comment('Ribbon color (Bootstrap Theme Color)')
					->change();
			});
		}
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
