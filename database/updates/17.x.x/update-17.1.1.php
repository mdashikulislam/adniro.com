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
	File::deleteDirectory(public_path('assets/plugins/summernote/font/'));
	File::deleteDirectory(public_path('assets/plugins/summernote/lang/'));
	File::deleteDirectory(public_path('assets/plugins/summernote/plugin/'));
	
	
	// Files
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.css'));
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.js'));
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.js.map'));
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.min.css'));
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.min.js'));
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.min.js.LICENSE.txt'));
	File::delete(public_path('assets/plugins/summernote/summernote-bs4.min.js.map'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.css'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.js'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.js.map'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.min.css'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.min.js'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.min.js.LICENSE.txt'));
	File::delete(public_path('assets/plugins/summernote/summernote-lite.min.js.map'));
	File::delete(public_path('assets/plugins/summernote/summernote.css'));
	File::delete(public_path('assets/plugins/summernote/summernote.js'));
	File::delete(public_path('assets/plugins/summernote/summernote.js.map'));
	File::delete(public_path('assets/plugins/summernote/summernote.min.css'));
	File::delete(public_path('assets/plugins/summernote/summernote.min.js'));
	File::delete(public_path('assets/plugins/summernote/summernote.min.js.LICENSE.txt'));
	File::delete(public_path('assets/plugins/summernote/summernote.min.js.map'));
	
	File::delete(resource_path('views/helpers/forms/fields/checkbox-switch.blade.php'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	//...
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
