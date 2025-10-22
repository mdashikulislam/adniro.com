<?php

use App\Exceptions\Custom\CustomException;
use App\Models\Setting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// Get the current migration list
$currentMigrationsNames = [
	'create_languages_table'              => '01_00_create_languages_table',
	'create_advertising_table'            => '02_00_create_advertising_table',
	'create_blacklist_table'              => '03_00_create_blacklist_table',
	'create_cache_table'                  => '04_00_create_cache_table',
	'create_categories_table'             => '05_01_create_categories_table',
	'create_fields_table'                 => '05_02_create_fields_table',
	'create_fields_options_table'         => '05_03_create_fields_options_table',
	'create_category_field_table'         => '05_04_create_category_field_table',
	'create_currencies_table'             => '06_00_create_currencies_table',
	'create_sections_table'               => '07_00_create_sections_table',
	'create_meta_tags_table'              => '08_00_create_meta_tags_table',
	'create_packages_table'               => '09_00_create_packages_table',
	'create_pages_table'                  => '10_00_create_pages_table',
	'create_payment_methods_table'        => '11_00_create_payment_methods_table',
	'create_report_types_table'           => '12_00_create_report_types_table',
	'create_settings_table'               => '13_00_create_settings_table',
	'create_countries_table'              => '14_01_create_countries_table',
	'create_subadmin1_table'              => '14_02_create_subadmin1_table',
	'create_subadmin2_table'              => '14_03_create_subadmin2_table',
	'create_cities_table'                 => '14_04_create_cities_table',
	'create_users_table'                  => '15_01_create_users_table',
	'create_user_social_logins_tables'    => '15_02_create_user_social_logins_table',
	'create_permission_tables'            => '15_03_create_permission_tables',
	'create_personal_access_tokens_table' => '15_04_create_personal_access_tokens_table',
	'create_posts_table'                  => '16_01_create_posts_table',
	'create_pictures_table'               => '16_02_create_pictures_table',
	'create_post_values_table'            => '16_03_create_post_values_table',
	'create_saved_posts_table'            => '17_00_create_saved_posts_table',
	'create_saved_search_table'           => '18_00_create_saved_search_table',
	'create_threads_table'                => '19_01_create_threads_table',
	'create_threads_messages_table'       => '19_02_create_threads_messages_table',
	'create_threads_participants_table'   => '19_03_create_threads_participants_table',
	'create_payments_table'               => '20_00_create_payments_table',
	'create_jobs_table'                   => '21_00_create_jobs_table',
];

// Get the current migrations files
$currentMigrationsFiles = collect($currentMigrationsNames)
	->mapWithKeys(function ($migration) {
		$migrationsDir = str(database_path('migrations'))->finish('/')->toString();
		$migrationFilePath = $migrationsDir . $migration . '.php';
		
		return [$migrationFilePath => $migration];
	});

// ===| FILES |===
try {
	
	// Directories
	File::deleteDirectory(app_path('Helpers/Common/Categories/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Admin/Auth/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Front/Auth/'));
	File::deleteDirectory(app_path('Services/Auth/Helpers/'));
	File::deleteDirectory(app_path('Services/Auth/Social/'));
	File::deleteDirectory(app_path('Services/Auth/Traits/RecognizedUserActions/'));
	File::deleteDirectory(app_path('Services/Auth/Traits/Verification/'));
	File::deleteDirectory(resource_path('views/admin/auth/'));
	File::deleteDirectory(resource_path('views/front/auth/'));
	File::deleteDirectory(resource_path('views/front/account/dashboard/'));
	File::deleteDirectory(resource_path('views/front/account/inc/'));
	
	// Files
	File::delete(public_path('assets/js/global.js'));
	File::delete(app_path('Http/Controllers/Web/Front/Account/CloseController.php'));
	File::delete(app_path('Http/Controllers/Web/Front/Account/DashboardController.php'));
	
	File::delete(app_path('Services/Auth/Traits/RecognizedUserActions.php'));
	File::delete(app_path('Services/Auth/Traits/VerificationTrait.php'));
	
	File::delete(resource_path('views/admin/layouts/auth.blade.php'));
	File::delete(resource_path('views/front/account/close.blade.php'));
	File::delete(resource_path('views/front/account/dashboard.blade.php'));
	
	File::delete(app_path('app/Notifications/EmailVerification.php'));
	File::delete(app_path('app/Notifications/PhoneVerification.php'));
	File::delete(app_path('app/Notifications/ResetPasswordNotification.php'));
	File::delete(app_path('app/Notifications/SendPasswordAndVerificationInfo.php'));
	File::delete(app_path('app/Notifications/UserActivated.php'));
	File::delete(app_path('app/Notifications/UserNotification.php'));
	
	// Delete old migrations files ============================================================
	// Get all (current & old) migrations files path
	$allMigrationsFiles = collect(File::glob(database_path('migrations') . '/*.php'));
	
	// Get obsolete migrations files (to delete)
	$fnIsNotObsolete = fn ($filePath) => $currentMigrationsFiles->keys()->contains($filePath);
	$obsoleteMigrationsFiles = $allMigrationsFiles->reject($fnIsNotObsolete)->toArray();
	
	// Delete the unused migrations files
	File::delete($obsoleteMigrationsFiles);
	// ========================================================================================
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	// settings
	$settingTable = (new Setting())->getTable();
	if (Schema::hasColumn($settingTable, 'key')) {
		// Add 'auth' as new setting
		$setting = Setting::where('key', 'auth')->first();
		if (empty($setting)) {
			$data = [
				'key'         => 'auth',
				'name'        => 'Authentication',
				'field'       => null,
				'value'       => null,
				'description' => 'Authentication Options',
				'parent_id'   => 0,
				'lft'         => 22,
				'rgt'         => 23,
				'depth'       => 1,
				'active'      => 1,
				'created_at'  => now()->format('Y-m-d H:i:s'),
			];
			DB::table('settings')->insert($data);
		}
		
		// settings (update 'social_auth' name)
		$setting = Setting::where('key', 'social_auth')->first();
		if (!empty($setting)) {
			$setting->name = 'Social Authentication';
			$setting->description = 'Social Network Authentication';
			$setting->save();
		}
		
		// settings (update 'social_link' name)
		$setting = Setting::where('key', 'social_link')->first();
		if (!empty($setting)) {
			$setting->name = 'Social Network Links';
			$setting->save();
		}
	}
	
	// Two-Factor Authentication
	if (!Schema::hasColumn('users', 'two_factor_enabled') && Schema::hasColumn('users', 'phone_verified_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->boolean('two_factor_enabled')->nullable()->default(false)->after('phone_verified_at');
		});
	}
	if (!Schema::hasColumn('users', 'two_factor_method') && Schema::hasColumn('users', 'two_factor_enabled')) {
		Schema::table('users', function (Blueprint $table) {
			$table->enum('two_factor_method', ['email', 'sms'])->nullable()->default('email')->after('two_factor_enabled');
		});
	}
	if (!Schema::hasColumn('users', 'two_factor_otp') && Schema::hasColumn('users', 'two_factor_method')) {
		Schema::table('users', function (Blueprint $table) {
			$table->string('two_factor_otp')->nullable()->after('two_factor_method');
		});
	}
	if (!Schema::hasColumn('users', 'otp_expires_at') && Schema::hasColumn('users', 'two_factor_otp')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('otp_expires_at')->nullable()->after('two_factor_otp');
		});
	}
	if (!Schema::hasColumn('users', 'last_otp_sent_at') && Schema::hasColumn('users', 'otp_expires_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('last_otp_sent_at')->nullable()->after('otp_expires_at');
		});
	}
	if (!Schema::hasColumn('users', 'otp_resend_attempts') && Schema::hasColumn('users', 'last_otp_sent_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->integer('otp_resend_attempts')->default(0)->after('last_otp_sent_at');
		});
	}
	if (!Schema::hasColumn('users', 'otp_resend_attempts_expires_at') && Schema::hasColumn('users', 'otp_resend_attempts')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('otp_resend_attempts_expires_at')->nullable()->after('otp_resend_attempts');
		});
	}
	if (!Schema::hasColumn('users', 'total_login_attempts') && Schema::hasColumn('users', 'otp_resend_attempts_expires_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->integer('total_login_attempts')->unsigned()->default(0)
				->comment("Total login attempts ever")
				->after('otp_resend_attempts_expires_at');
		});
	}
	if (!Schema::hasColumn('users', 'total_otp_resend_attempts') && Schema::hasColumn('users', 'total_login_attempts')) {
		Schema::table('users', function (Blueprint $table) {
			$table->integer('total_otp_resend_attempts')->unsigned()->default(0)
				->comment("Total resend attempts ever")
				->after('total_login_attempts');
		});
	}
	if (!Schema::hasColumn('users', 'locked_at') && Schema::hasColumn('users', 'total_otp_resend_attempts')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('locked_at')->nullable()->after('total_otp_resend_attempts');
		});
	}
	if (!Schema::hasColumn('users', 'suspended_at') && Schema::hasColumn('users', 'last_login_at')) {
		Schema::table('users', function (Blueprint $table) {
			$table->timestamp('suspended_at')->nullable()->after('last_login_at');
		});
	}
	
	// Create the "user_social_logins" table
	if (!Schema::hasTable('user_social_logins')) {
		Schema::create('user_social_logins', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('provider')->nullable();
			$table->string('provider_id')->nullable();
			$table->string('token')->nullable();
			$table->timestamps();
			
			$table->unique(['user_id', 'provider']);
			$table->unique(['provider', 'provider_id']);
			$table->index(['token']);
		});
	}
	
	// Bind data from "users" to "user_social_logins" table
	if (
		Schema::hasTable('user_social_logins')
		&& (
			Schema::hasColumn('users', 'provider')
			&& Schema::hasColumn('users', 'provider_id')
		)
	) {
		DB::table('users')->lazyById()->each(function ($user) {
			$createdAt = !empty($user->created_at) ? $user->created_at : now()->format('Y-m-d H:i:s');
			$updatedAt = !empty($user->updated_at) ? $user->updated_at : now()->format('Y-m-d H:i:s');
			
			$socialAccount = DB::table('user_social_logins')
				->where('user_id', $user->id)
				->where('provider', $user->provider)
				->first();
			
			$socialAccount2 = DB::table('user_social_logins')
				->where('provider', $user->provider)
				->where('provider_id', $user->provider_id)
				->first();
			
			if (
				empty($socialAccount) && empty($socialAccount2)
				&& !empty($user->provider)
				&& !empty($user->provider_id)
			) {
				$socialAccountData = [
					'user_id'     => $user->id,
					'provider'    => $user->provider,
					'provider_id' => $user->provider_id,
					'token'       => null,
					'created_at'  => $createdAt,
					'updated_at'  => $updatedAt,
				];
				DB::table('user_social_logins')->insert($socialAccountData);
			}
			
			if (Schema::hasColumn('users', 'blocked')) {
				$isSuspended = (isset($user->blocked) && $user->blocked);
				$suspendedAt = $isSuspended ? now() : null;
				DB::table('users')->where('id', $user->id)->update(['suspended_at' => $suspendedAt]);
			}
		});
	}
	
	if (Schema::hasColumn('users', 'closed')) {
		Schema::table('users', fn (Blueprint $table) => $table->dropColumn('closed'));
	}
	if (Schema::hasColumn('users', 'blocked')) {
		Schema::table('users', fn (Blueprint $table) => $table->dropColumn('blocked'));
	}
	
	// Count entries with social account in "users" and "user_social_logins" tables
	$countUsersWithSocialAccount = 0;
	$countSocialAccounts = 0;
	if (
		Schema::hasColumn('users', 'provider')
		&& Schema::hasColumn('users', 'provider_id')
	) {
		$countUsersWithSocialAccount = DB::table('users')->whereNotNull('provider')->whereNotNull('provider_id')->count();
	}
	if (Schema::hasTable('user_social_logins')) {
		$countSocialAccounts = DB::table('user_social_logins')->count();
	}
	
	// Drop the "provider" and "provider_id" columns from the "users" table
	if (
		($countUsersWithSocialAccount > 0 && $countSocialAccounts > 0)
		|| ($countUsersWithSocialAccount == 0)
	) {
		if (Schema::hasColumn('users', 'provider')) {
			Schema::table('users', fn (Blueprint $table) => $table->dropColumn('provider'));
		}
		if (Schema::hasColumn('users', 'provider_id')) {
			Schema::table('users', fn (Blueprint $table) => $table->dropColumn('provider_id'));
		}
	}
	
	
	// Update the migrations files in DB ======================================================
	// Get the migrations that can be saved in database
	$fnFileExists = fn ($item, $filePath) => File::exists($filePath);
	$migrationsToSaveInDb = $currentMigrationsFiles->filter($fnFileExists)->toArray();
	
	// Truncate the migrations table & Insert the migrations
	if (!empty($migrationsToSaveInDb)) {
		DB::table('migrations')->truncate();
		
		foreach ($migrationsToSaveInDb as $migrationFilePath => $migration) {
			DB::table('migrations')->insert(['migration' => $migration, 'batch' => 1]);
		}
	}
	// ========================================================================================
	
	// posts
	if (!Schema::hasColumn('posts', 'otp_expires_at') && Schema::hasColumn('posts', 'phone_verified_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('otp_expires_at')->nullable()->after('phone_verified_at');
		});
	}
	if (!Schema::hasColumn('posts', 'last_otp_sent_at') && Schema::hasColumn('posts', 'otp_expires_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('last_otp_sent_at')->nullable()->after('otp_expires_at');
		});
	}
	if (!Schema::hasColumn('posts', 'otp_resend_attempts') && Schema::hasColumn('posts', 'last_otp_sent_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->integer('otp_resend_attempts')->default(0)->after('last_otp_sent_at');
		});
	}
	if (!Schema::hasColumn('posts', 'otp_resend_attempts_expires_at') && Schema::hasColumn('posts', 'otp_resend_attempts')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('otp_resend_attempts_expires_at')->nullable()->after('otp_resend_attempts');
		});
	}
	if (!Schema::hasColumn('posts', 'total_otp_resend_attempts') && Schema::hasColumn('posts', 'otp_resend_attempts_expires_at')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->integer('total_otp_resend_attempts')->unsigned()->default(0)
				->comment("Total resend attempts ever")
				->after('otp_resend_attempts_expires_at');
		});
	}
	if (!Schema::hasColumn('posts', 'locked_at') && Schema::hasColumn('posts', 'total_otp_resend_attempts')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->timestamp('locked_at')->nullable()->after('total_otp_resend_attempts');
		});
	}
	
	if (Schema::hasColumn('posts', 'fb_profile')) {
		Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('fb_profile'));
	}
	if (Schema::hasColumn('posts', 'partner')) {
		Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('partner'));
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
