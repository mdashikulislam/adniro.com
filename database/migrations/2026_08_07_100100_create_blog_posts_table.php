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
		Schema::create('blog_posts', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->integer('category_id')->unsigned()->nullable();
			$table->bigInteger('user_id')->unsigned()->nullable();
			$table->string('title', 191);
			$table->string('slug', 191)->nullable();
			$table->string('image_path', 255)->nullable();
			$table->text('excerpt')->nullable();
			$table->mediumText('content')->nullable();
			$table->text('seo_title')->nullable();
			$table->text('seo_description')->nullable();
			$table->text('seo_keywords')->nullable();
			$table->integer('views')->unsigned()->nullable()->default(0);
			$table->boolean('featured')->nullable()->default(false);
			$table->boolean('active')->nullable()->default(true);
			$table->timestamp('published_at')->nullable();
			$table->timestamps();

			$table->unique(['slug']);
			$table->index(['category_id']);
			$table->index(['user_id']);
			$table->index(['active']);
			$table->index(['featured']);
			$table->index(['published_at']);
			$table->index(['active', 'published_at']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('blog_posts');
	}
};
