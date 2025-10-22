<?php

use App\Enums\ThemePreference;
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
		Schema::create('users', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('country_code', 2)->nullable();
			$table->string('language_code', 10)->nullable();
			$table->tinyInteger('user_type_id')->unsigned()->nullable();
			$table->integer('gender_id')->unsigned()->nullable();
			$table->string('name', 100);
			$table->string('photo_path', 255)->nullable();
			$table->string('about', 255)->nullable();
			
			$table->enum('auth_field', ['email', 'phone'])->nullable()->default('email');
			$table->string('email', 191)->nullable();
			$table->string('phone', 60)->nullable();
			$table->string('phone_national', 30)->nullable();
			$table->string('phone_country', 2)->nullable();
			$table->string('username', 100)->nullable();
			$table->string('password', 60)->nullable();
			$table->string('remember_token', 191)->nullable();
			$table->string('email_token', 191)->nullable()->comment("Email verification token or OTP");
			$table->string('phone_token', 191)->nullable()->comment("Phone number verification OTP");
			$table->timestamp('email_verified_at')->nullable();
			$table->timestamp('phone_verified_at')->nullable();
			
			$table->boolean('two_factor_enabled')->nullable()->default(false);
			$table->enum('two_factor_method', ['email', 'sms'])->nullable()->default('email');
			$table->string('two_factor_otp')->nullable()->comment("Two-Factor Authentication OTP");
			$table->timestamp('otp_expires_at')->nullable()->comment("Used for account verification & two-factor");
			$table->timestamp('last_otp_sent_at')->nullable()->comment("Used for account verification & two-factor");
			$table->integer('otp_resend_attempts')->unsigned()->default(0)->comment("Used for account verification & two-factor");
			$table->timestamp('otp_resend_attempts_expires_at')->nullable()->comment("Used for account verification & two-factor");
			$table->integer('total_login_attempts')->unsigned()->default(0)->comment("Total login attempts ever");
			$table->integer('total_otp_resend_attempts')->unsigned()->default(0)->comment("Total resend attempts ever");
			$table->timestamp('locked_at')->nullable()->comment("When the total login or OTP resend attempts is reached");
			
			$table->boolean('is_admin')->nullable()->default(false);
			$table->boolean('can_be_impersonated')->nullable()->default(true);
			$table->boolean('phone_hidden')->nullable()->default(false);
			$table->boolean('disable_comments')->nullable()->default(false);
			$table->string('create_from_ip', 50)->nullable()->comment('IP address of creation');
			$table->string('latest_update_ip', 50)->nullable()->comment('Latest update IP address');
			$table->boolean('accept_terms')->nullable()->default(false);
			$table->boolean('accept_marketing_offers')->nullable()->default(false);
			
			$themes = implode(', ', ThemePreference::values());
			$table->string('theme_preference')->nullable()->comment("User theme preference: $themes");
			
			$table->string('time_zone', 50)->nullable();
			$table->boolean('featured')->nullable()->default(false)->comment('Need to be cleared form a cron tab command');
			$table->datetime('last_activity')->nullable();
			$table->datetime('last_login_at')->nullable();
			$table->timestamp('suspended_at')->nullable();
			$table->timestamp('deleted_at')->nullable();
			$table->timestamps();
			
			$table->index(['country_code']);
			$table->index(['user_type_id']);
			$table->index(['auth_field']);
			$table->index(['email']);
			$table->index(['phone']);
			$table->index(['phone_country']);
			$table->index(['username']);
			$table->index(['email_verified_at']);
			$table->index(['phone_verified_at']);
			$table->index(['is_admin']);
			$table->index(['can_be_impersonated']);
		});
		
		Schema::create('password_reset_tokens', function (Blueprint $table) {
			$table->string('email', 191)->nullable();
			$table->string('phone', 191)->nullable();
			$table->string('phone_country', 2)->nullable();
			$table->string('token', 191)->nullable();
			$table->timestamp('created_at')->nullable();
			
			$table->index(['email']);
			$table->index(['phone']);
			$table->index(['token']);
		});
		
		Schema::create('sessions', function (Blueprint $table) {
			$table->string('id')->primary();
			$table->foreignId('user_id')->nullable()->index();
			$table->string('ip_address', 45)->nullable();
			$table->text('user_agent')->nullable();
			$table->text('payload');
			$table->integer('last_activity')->index();
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('users');
		Schema::dropIfExists('password_reset_tokens');
		Schema::dropIfExists('sessions');
	}
};
