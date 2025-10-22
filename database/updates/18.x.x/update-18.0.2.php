<?php

use App\Exceptions\Custom\CustomException;
use App\Helpers\Common\DBUtils;
use App\Helpers\Common\DBUtils\DBIndex;
use App\Models\Permission;
use Database\Seeders\MenuItemSeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ===| FILES |===
try {
	
	// Directories
	$owlCarouselDir = public_path('assets/plugins/owlcarousel/');
	if (File::isDirectory($owlCarouselDir)) {
		File::deleteDirectory($owlCarouselDir);
	}
	
	
	// Files
	File::delete(app_path('Exceptions/Custom/AppVersionNotFound.php'));
	File::delete(app_path('Exceptions/Custom/InvalidPurchaseCode.php'));
	
	File::delete(resource_path('views/admin/panel/buttons/create.blade.php'));
	File::delete(resource_path('views/admin/panel/buttons/delete.blade.php'));
	File::delete(resource_path('views/admin/panel/buttons/parent.blade.php'));
	File::delete(resource_path('views/admin/panel/buttons/preview.blade.php'));
	File::delete(resource_path('views/admin/panel/buttons/reorder.blade.php'));
	File::delete(resource_path('views/admin/panel/buttons/revisions.blade.php'));
	File::delete(resource_path('views/admin/panel/buttons/update.blade.php'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	// permissions & roles
	Permission::ensureDefaultRolesAndPermissionsExist();
	Permission::ensureSuperAdminExists();
	
	// sections, settings, domain_sections, domain_settings
	$settingTables = ['sections', 'settings', 'domain_sections', 'domain_settings'];
	foreach ($settingTables as $table) {
		if (!Schema::hasTable($table)) {
			continue;
		}
		
		$isDomainMappingTable = str_starts_with($table, 'domain_');
		$isSectionsTable = ($table == 'sections' || str_ends_with($table, 'sections'));
		
		// Check if indexes exist, and drop them
		if ($isDomainMappingTable) {
			if ($isSectionsTable) {
				DBIndex::dropIndexIfExists($table, ['country_code', 'belongs_to', 'key'], 'unique');
			} else {
				DBIndex::dropIndexIfExists($table, ['country_code', 'key'], 'unique');
			}
			DBIndex::dropIndexIfExists($table, 'key');
		} else {
			if ($isSectionsTable) {
				DBIndex::dropIndexIfExists($table, ['belongs_to', 'key'], 'unique');
				DBIndex::dropIndexIfExists($table, 'key');
			} else {
				DBIndex::dropIndexIfExists($table, 'key', 'unique');
			}
		}
		
		// label
		if (
			Schema::hasColumn($table, 'name')
			&& !Schema::hasColumn($table, 'label')
		) {
			Schema::table($table, function (Blueprint $table) {
				$table->renameColumn('name', 'label');
			});
		}
		
		// name (ex: key)
		if (
			Schema::hasColumn($table, 'key')
			&& !Schema::hasColumn($table, 'name')
		) {
			Schema::table($table, function (Blueprint $table) {
				$table->renameColumn('key', 'name');
			});
		}
		
		// fields
		if (
			Schema::hasColumn($table, 'field')
			&& !Schema::hasColumn($table, 'fields')
		) {
			Schema::table($table, function (Blueprint $table) {
				$table->renameColumn('field', 'fields');
			});
		}
		
		// field_values
		if (
			Schema::hasColumn($table, 'value')
			&& !Schema::hasColumn($table, 'field_values')
		) {
			Schema::table($table, function (Blueprint $table) {
				$table->renameColumn('value', 'field_values');
			});
		}
		
		// Create the new indexes
		if ($isDomainMappingTable) {
			if ($isSectionsTable) {
				DBIndex::createIndexIfNotExists($table, ['country_code', 'belongs_to', 'name'], 'unique');
			} else {
				DBIndex::createIndexIfNotExists($table, ['country_code', 'name'], 'unique');
			}
			DBIndex::createIndexIfNotExists($table, 'name');
		} else {
			if ($isSectionsTable) {
				DBIndex::createIndexIfNotExists($table, ['belongs_to', 'name'], 'unique');
				DBIndex::createIndexIfNotExists($table, 'name');
			} else {
				DBIndex::createIndexIfNotExists($table, 'name', 'unique');
			}
		}
	}
	
	// pages
	$tableName = 'pages';
	if (Schema::hasTable($tableName)) {
		// pages.target_blank
		if (Schema::hasColumn($tableName, 'target_blank')) {
			Schema::table($tableName, function (Blueprint $table) {
				$table->dropColumn('target_blank');
			});
		}
		
		// pages.target
		if (
			!Schema::hasColumn($tableName, 'target')
			&& Schema::hasColumn($tableName, 'title_color')
		) {
			Schema::table($tableName, function (Blueprint $table) {
				$table->string('target')->nullable()->default('_self')->after('title_color');
			});
		}
	}
	
	// menus
	$menusTable = 'menus';
	if (!Schema::hasTable($menusTable)) {
		Schema::create($menusTable, function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('location')->unique()->comment("'header', 'footer', 'sidebar', etc.");
			$table->string('description')->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->integer('lft')->unsigned()->nullable()->default(0);
			$table->integer('rgt')->unsigned()->nullable()->default(0);
			$table->integer('depth')->unsigned()->nullable()->default(0);
			$table->boolean('active')->default(true);
			$table->timestamps();
			
			$table->unique(['name', 'location']);
		});
	}
	
	// menu_items
	$menuItemsTable = 'menu_items';
	if (!Schema::hasTable($menuItemsTable)) {
		Schema::create($menuItemsTable, function (Blueprint $table) use ($menusTable, $menuItemsTable) {
			$table->id();
			$table->foreignId('menu_id')->constrained($menusTable)->cascadeOnDelete();
			$table->foreignId('parent_id')->nullable()->constrained($menuItemsTable)->cascadeOnDelete();
			$table->enum('type', ['link', 'button', 'divider', 'title'])->default('link');
			$table->string('icon')->nullable(); // Font Awesome, Bootstrap Icons, etc.
			$table->json('label')->nullable(); // Translatable field using JSON
			$table->string('url_type')->nullable()->comment('route, internal, external, or null');
			$table->string('route_name')->nullable();
			$table->json('route_parameters')->nullable();
			$table->string('url')->nullable();
			$table->string('target')->nullable()->comment('_self, _blank, _parent, _top');
			$table->boolean('nofollow')->default(false);
			$table->string('btn_class')->nullable();
			$table->boolean('btn_outline')->nullable()->default(false);
			$table->string('css_class')->nullable();
			$table->json('conditions')->nullable(); // Custom conditions for visibility
			$table->json('html_attributes')->nullable(); // Additional HTML attributes
			$table->json('roles')->nullable(); // Array of required roles
			$table->json('permissions')->nullable(); // Array of required permissions
			$table->string('description')->nullable()->comment('For details or notes');
			$table->integer('lft')->unsigned()->nullable()->default(0);
			$table->integer('rgt')->unsigned()->nullable()->default(0);
			$table->integer('depth')->unsigned()->nullable()->default(0);
			$table->boolean('active')->default(true);
			$table->timestamps();
			
			$table->index(['menu_id', 'parent_id', 'lft', 'rgt']);
			$table->index(['active']);
		});
	}
	
	// 'menu' & 'menu_items' data seeding
	if (Schema::hasTable($menusTable) && Schema::hasTable($menuItemsTable)) {
		Schema::disableForeignKeyConstraints();
		
		DB::table($menuItemsTable)->truncate();
		DB::statement('ALTER TABLE ' . DBUtils::table($menuItemsTable) . ' AUTO_INCREMENT = 1;');
		
		DB::table($menusTable)->truncate();
		DB::statement('ALTER TABLE ' . DBUtils::table($menusTable) . ' AUTO_INCREMENT = 1;');
		
		$menuSeeder = new MenuSeeder();
		$menuSeeder->run();
		
		$menuSeeder = new MenuItemSeeder();
		$menuSeeder->run();
		
		Schema::enableForeignKeyConstraints();
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
