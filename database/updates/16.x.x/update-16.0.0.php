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
	
	// app/Helpers
	File::deleteDirectory(app_path('Helpers/Categories/'));
	File::deleteDirectory(app_path('Helpers/DBTool/'));
	File::deleteDirectory(app_path('Helpers/Files/'));
	File::deleteDirectory(app_path('Helpers/Functions/'));
	File::deleteDirectory(app_path('Helpers/GeoIP/'));
	File::deleteDirectory(app_path('Helpers/Response/'));
	File::delete(app_path('Helpers/Arr.php'));
	File::delete(app_path('Helpers/Cookie.php'));
	File::delete(app_path('Helpers/Curl.php'));
	File::delete(app_path('Helpers/Date.php'));
	File::delete(app_path('Helpers/DBTool.php'));
	File::delete(app_path('Helpers/DotenvEditor.php'));
	File::delete(app_path('Helpers/GeoIP.php'));
	File::delete(app_path('Helpers/Ip.php'));
	File::delete(app_path('Helpers/Num.php'));
	File::delete(app_path('Helpers/PhpArrayFile.php'));
	File::delete(app_path('Helpers/RandomColor.php'));
	File::delete(app_path('Helpers/SystemLocale.php'));
	File::delete(app_path('Helpers/UrlQuery.php'));
	File::delete(app_path('Helpers/VideoEmbedder.php'));
	File::delete(app_path('Helpers/VideoIdExtractor.php'));
	
	// app/Services
	File::deleteDirectory(app_path('Services/Functions/'));
	File::deleteDirectory(app_path('Services/Lang/'));
	File::deleteDirectory(app_path('Services/Localization/'));
	File::deleteDirectory(app_path('Services/Search/'));
	File::deleteDirectory(app_path('Services/Thumbnail/'));
	File::deleteDirectory(app_path('Services/UrlGen/'));
	File::delete(app_path('Services/Payment/PaymentTrait.php'));
	File::delete(app_path('Services/Payment/PaymentUrlsTrait.php'));
	File::delete(app_path('Services/Payment.php'));
	File::delete(app_path('Services/Referrer.php'));
	File::delete(app_path('Services/RemoveFromString.php'));
	File::delete(app_path('Services/ThumbnailParams.php'));
	File::delete(app_path('Services/ThumbnailService.php'));
	File::delete(app_path('Services/UrlGen.php'));
	
	// Controllers/Api
	File::deleteDirectory(app_path('Http/Controllers/Api/Auth/Helpers/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Auth/Social/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Auth/Traits/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Category/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Country/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Page/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Payment/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Picture/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Post/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Section/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/Thread/'));
	File::deleteDirectory(app_path('Http/Controllers/Api/User/'));
	
	// Controllers/Web/Front
	File::deleteDirectory(app_path('Http/Controllers/Web/Front/Ajax/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Front/Post/Traits/'));
	File::delete(app_path('Http/Controllers/Web/Front/Post/ShowController.php'));
	File::delete(app_path('Http/Controllers/Web/Front/Post/CreateOrEdit/Traits/CategoriesTrait.php'));
	
	// app/Rules
	File::delete(app_path('app/Rules/EmailRule.php'));
	
	// bootstrap-waitingfor
	File::deleteDirectory(public_path('assets/plugins/bootstrap-waitingfor/'));
	
	// .ENV
	$needToBeSaved = false;
	if (DotenvEditor::keyExists('APP_HTTP_CLIENT')) {
		DotenvEditor::deleteKey('APP_HTTP_CLIENT');
		$needToBeSaved = true;
	}
	if ($needToBeSaved) {
		DotenvEditor::save();
	}
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	//...
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
