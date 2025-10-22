<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Services\Auth\App\Models;

use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

trait Authenticatable
{
	/**
	 * Increment failed login attempts and check for lockout
	 *
	 * @return void
	 */
	public function incrementLoginAttempts(): void
	{
		// Don't lock admin users
		$isAdminUser = doesUserHavePermission($this, Permission::getStaffPermissions());
		if ($isAdminUser) return;
		
		// Increment the login attempts
		// ---
		$maxLoginLockoutAttempts = maxLoginLockoutAttempts();
		
		$this->timestamps = false;
		$this->total_login_attempts = ($this->total_login_attempts ?? 0) + 1;
		
		if ($this->total_login_attempts >= $maxLoginLockoutAttempts && $maxLoginLockoutAttempts > 0) {
			$this->locked_at = now();
		}
		
		$this->saveQuietly();
		$this->timestamps = true;
	}
	
	/**
	 * Check if the user’s account is currently locked
	 *
	 * @return bool
	 */
	public function isLocked(): bool
	{
		$lockoutDurationInMinutes = lockoutDurationInMinutes();
		
		if (!empty($this->locked_at)) {
			$isAdminUser = doesUserHavePermission($this, Permission::getStaffPermissions());
			$unlockTime = $this->locked_at->copy()->addMinutes($lockoutDurationInMinutes);
			
			if (now()->lessThan($unlockTime) && !$isAdminUser) {
				return true; // Still locked
			}
			
			// Unlock if duration has passed
			$this->locked_at = null;
			$this->total_otp_resend_attempts = 0; // Reset attempts on unlock
			$this->total_login_attempts = 0;      // Reset attempts on unlock
			$this->saveQuietly();
		}
		
		return false;
	}
	
	/**
	 * Reset lockout-related counters (optional, for manual unlocking)
	 *
	 * @return void
	 */
	public function resetLockout(): void
	{
		$this->timestamps = false;
		
		// Unlock
		$this->locked_at = null;
		
		// Reset attempts on unlock
		$this->total_login_attempts = 0;
		$this->otp_resend_attempts = 0;
		$this->otp_resend_attempts_expires_at = null;
		$this->total_otp_resend_attempts = 0;
		
		$this->saveQuietly();
		
		$this->timestamps = true;
	}
	
	/**
	 * Save the new two-factor authentication code and set its expiration
	 *
	 * @param string $code
	 * @return void
	 */
	public function generateTwoFactorCode(string $code): void
	{
		$otpExpireTime = otpExpireTimeInSeconds();
		$hashedCode = Hash::make($code);
		
		$this->timestamps = false; // Prevent updating the `updated_at` column
		$this->two_factor_otp = $hashedCode;
		$this->otp_expires_at = now()->addSeconds($otpExpireTime);
		$this->last_otp_sent_at = now();
		$this->saveQuietly();
		$this->timestamps = true;
	}
	
	/**
	 * Reset the two-factor authentication code and expiration
	 *
	 * @return void
	 */
	public function resetTwoFactorCode(): void
	{
		$this->timestamps = false;
		$this->two_factor_otp = null;
		$this->otp_expires_at = null;
		$this->last_otp_sent_at = null; // added
		$this->saveQuietly();
		$this->timestamps = true;
	}
	
	/**
	 * Check if the user can request a new OTP based on cooldown and attempt limits
	 *
	 * @return bool
	 */
	public function canRequestNewOtp(): bool
	{
		$cooldownInSecond = otpCooldownInSeconds();
		$maxAttempts = otpResendMaxAttempts();
		$decayMinutes = otpResendDecayInMinutes();
		
		if ($this->isLocked()) {
			return false;
		}
		
		// Check if the cooldown period has not elapsed
		if (
			!empty($this->last_otp_sent_at)
			&& $this->last_otp_sent_at->diffInSeconds(now()) < $cooldownInSecond
		) {
			return false; // Too soon to request another OTP
		}
		
		// Reset attempts if the decay period has passed
		if (
			!empty($this->otp_resend_attempts_expires_at)
			&& $this->otp_resend_attempts_expires_at->lessThan(now())
		) {
			$this->otp_resend_attempts = 0; // Reset attempts
			$this->otp_resend_attempts_expires_at = now()->addMinutes($decayMinutes); // Set new reset time
			$this->saveQuietly();
		}
		
		if (is_null($this->otp_resend_attempts)) {
			$this->otp_resend_attempts = 0;
			$this->saveQuietly();
		}
		
		// Check if the user has exceeded the maximum resend attempts
		return ($this->otp_resend_attempts < $maxAttempts && $maxAttempts > 0);
	}
	
	/**
	 * Increment the OTP resend attempt counter, set initial reset time if needed and check for lockout
	 *
	 * @return void
	 */
	public function incrementOtpAttempts(): void
	{
		$decayMinutes = otpResendDecayInMinutes();
		$maxResendLockoutAttempts = maxResendLockoutAttempts();
		
		$this->timestamps = false;
		if (empty($this->otp_resend_attempts_expires_at)) {
			$this->otp_resend_attempts_expires_at = now()->addMinutes($decayMinutes);
		}
		$this->otp_resend_attempts = ($this->otp_resend_attempts ?? 0) + 1;
		$this->total_otp_resend_attempts = ($this->total_otp_resend_attempts ?? 0) + 1;
		
		if ($this->total_otp_resend_attempts >= $maxResendLockoutAttempts && $maxResendLockoutAttempts > 0) {
			$this->locked_at = now();
		}
		
		$this->saveQuietly();
		$this->timestamps = true;
	}
}
