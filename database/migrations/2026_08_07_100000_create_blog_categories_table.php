<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::create('blog_categories', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('parent_id')->unsigned()->nullable();
			$table->string('name', 191);
			$table->string('slug', 191)->nullable();
			$table->string('image_path', 255)->nullable();
			$table->text('description')->nullable();
			$table->text('seo_title')->nullable();
			$table->text('seo_description')->nullable();
			$table->text('seo_keywords')->nullable();
			$table->integer('lft')->unsigned()->nullable()->default(0);
			$table->integer('rgt')->unsigned()->nullable()->default(0);
			$table->integer('depth')->unsigned()->nullable()->default(0);
			$table->boolean('active')->nullable()->default(true);
			$table->timestamps();

			$table->unique(['slug']);
			$table->index(['parent_id']);
			$table->index(['lft']);
			$table->index(['active']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('blog_categories');
	}
};
