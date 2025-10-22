<?php

return [
	
	/*
	|--------------------------------------------------------------------------
	| Authentication Language Lines
	|--------------------------------------------------------------------------
	|
	| The following language lines are used during authentication for various
	| messages that we need to display to the user. You are free to modify
	| these language lines according to your application's requirements.
	|
	*/
	
	// General Authentication Messages
	'failed'            => 'These credentials do not match our records.',
	'throttle'          => 'Too many login attempts. Please try again in :seconds seconds.',
	'readable_throttle' => 'Too many login attempts. Please try again in :humanReadableTime.',
	
	// Logout Messages
	'logout_successful' => 'You have been logged out. See you soon.',
	'logout_failed'     => 'An error occurred and the logout failed.',
	
	// Cover Texts for Authentication Pages
	'default_cover_title'               => 'Welcome to :appName!',
	'default_cover_description'         => 'We are glad to see you! Get access to your account, profile and preferences.',
	'login_cover_title'                 => 'Welcome back!',
	'login_cover_description'           => 'We\'re happy to see you again! Access your account, profile, and preferences.',
	'register_cover_title'              => 'Looks like you\'re new here!',
	'register_cover_description'        => 'Do you have something to sell, rent, a service to offer, or a job posting? Join our community in just a few minutes! Sign up now to get started.',
	'password_forgot_cover_title'       => 'Don\'t worry,',
	'password_forgot_cover_description' => 'We\'re here to help you recover your password.',
	'password_reset_cover_title'        => 'Welcome back!',
	'password_reset_cover_description'  => 'We are glad to see you again! Get access to your account, profile and preferences.',
	'security_cover_title'              => 'Your account security is our priority.',
	'security_cover_description'        => 'We\'re happy to see you again! Access your account, profile, and preferences seamlessly.',
	
	// User Input Fields
	'email'                             => 'Email',
	'phone'                             => 'Phone',
	'email_address'                     => 'Email Address',
	'phone_number'                      => 'Phone Number',
	'username'                          => 'Username',
	'email_or_phone'                    => 'Email or Phone',
	'email_or_username'                 => 'Email or Username',
	'sms_code'                          => 'SMS Code',
	'otp_validation'                    => 'Validate OTP',
	'otp_validation_description'        => 'Please enter the OTP (one time password) to verify your account. A Code has been sent to <span class="text-dark">:fieldHiddenValue</span>',
	'verify'                            => 'Verify',
	'not_received_link'                 => 'Not received the verification link?',
	'not_received_code'                 => 'Not received your code?',
	'resend_it'                         => 'Resend It',
	'expired_link'                      => 'The link has expired. Please try again.',
	'invalid_link'                      => 'The provided link is invalid.',
	'maximum_link_resend_attempts_reached' => 'Maximum link resend attempts reached. Please try again later.',
	'wait_before_request_new_link'      => 'Please wait :humanReadableTime seconds before requesting a new link.',
	'link_sent'                         => 'Link sent successfully.',
	'new_link_sent'                     => 'A new link has been sent.',
	'resend_link'                       => 'Resend Link',
	
	// Token and Code Verification
	'token'                             => 'Token',
	'code'                              => 'Code',
	'code_received_by_email'            => 'Code received by Email',
	'code_received_by_sms'              => 'Code received by SMS',
	'code_received_by'                  => 'Code received by SMS or Email',
	'verification_code'                 => 'Verification Code',
	'enter_verification_code'           => 'Enter the verification code',
	'enter_code_received_by_email'      => 'Enter the code you received by Email in the field below',
	'enter_code_received_by_sms'        => 'Enter the code you received by SMS in the field below',
	'enter_code_received_by'            => 'Enter the code you received by SMS or Email in the field below',
	'invalid_provided_code'             => 'The provided code is invalid.',
	'credentials_dont_match'            => 'These credentials do not match our records.',
	'code_doesnt_match'                 => 'The code does not match your email or phone number.',
	'code_doesnt_match_email'           => 'The code does not match your email.',
	'code_doesnt_match_phone'           => 'The code does not match your phone number.',
	'provided_information_doesnt_match' => 'The provided information is not associated with any account. Please double-check your input and try again.',
	
	// Authentication Actions
	'register'                          => 'Register',
	'register_description'              => 'Sign up by entering your account details in the form below.',
	'login'                             => 'Login',
	'login_description_email'           => 'Enter your email and password to access your account.',
	'login_description_phone'           => 'Enter your phone number and password to access your account.',
	'login_description'                 => 'Enter your email or phone number and password to access your account.',
	'logout'                            => 'Logout',
	'sign_up'                           => 'Sign Up',
	'sign_in'                           => 'Sign In',
	'sign_out'                          => 'Sign Out',
	'log_in'                            => 'Log In',
	'log_out'                           => 'Log Out',
	'submitting'                        => 'Submitting...',
	
	// Authentication Prompts and Options
	'remember_me'                       => 'Remember me',
	'forgot_password'                   => 'Forgot password?',
	'dont_have_account'                 => 'Don\'t have an account?',
	'already_have_account'              => 'Already have an account?',
	'create_account'                    => 'Create account',
	'accept_terms_for_account'          => 'By creating an account you agree to our <a href=":serviceTermsUrl">Terms of Service</a> and <a href=":privacyPolicyUrl">Privacy Policy</a>',
	'accept_terms_for_usage'            => 'By continuing you agree to our <a href=":serviceTermsUrl">Terms of Service</a> and <a href=":privacyPolicyUrl">Privacy Policy</a>',
	
	// Password Reset and Recovery
	'forgotten_password'                => 'Forgot Password',
	'forgotten_password_description_email' => 'Enter the email address associated with your account.',
	'forgotten_password_description_phone' => 'Enter the mobile number associated with your account.',
	'forgotten_password_description'    => 'Enter the email address or mobile number associated with your account.',
	'forgot_password_hint_email'        => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
	'forgot_password_hint_phone'        => 'Forgot your password? No problem. Just let us know your phone number and we will send a password reset code by SMS that will allow you to choose a new one.',
	'reset_password'                    => 'Reset Password',
	'reset_password_description_email'  => 'Enter your account email address and a new password to reset your access.',
	'reset_password_description_phone'  => 'Enter your account phone number and a new password to reset your access.',
	'reset_password_description'        => 'Enter your account email address or mobile number and a new password to reset your access.',
	
	// Account Information and Updates
	'account_info'                      => 'Account info',
	'change_info'                       => 'Change info',
	'information_updated'               => 'Your information has been updated.',
	
	// Password Management
	'password'                          => 'Password',
	'confirm_password'                  => 'Confirm Password',
	'change_password'                   => 'Change Password',
	'current_password'                  => 'Current Password',
	'new_password'                      => 'New Password',
	'confirm_new_password'              => 'Confirm New Password',
	'forgot_password_question'          => 'Forgot your password?',
	'continue'                          => 'Continue',
	'send_password_reset_link'          => 'Send Password Reset Link',
	'wrong_password'                    => 'The current password your entered is wrong.',
	'password_updated'                  => 'Your password has been changed.',
	'password_reset'                    => 'Your password has been reset.',
	'registration_closed'               => 'Registration is closed.',
	'back_to_login'                     => 'Back to Login',
	'back_to_home'                      => 'Back to Home',
	'congratulations'                   => 'Congratulations',
	
	// Password Tips
	'password_tips_title'     => 'Password Tips & Requirements',
	'password_tip_length'     => 'Must be :min to :max characters long',
	'password_tip_letter'     => 'Must include at least one letter (A-Z, a-z)',
	'password_tip_mixed_case' => 'Must include both uppercase and lowercase letters',
	'password_tip_number'     => 'Must include at least one number (0-9)',
	'password_tip_symbol'     => 'Must include at least one special character',
	'password_tip_common'     => 'Avoid common words, phrases, or personal info',
	
	// Password Visibility Toggles
	'hide_password'                     => 'Hide the password',
	'show_password'                     => 'Show the password',
	'hide'                              => 'Hide',
	'show'                              => 'Show',
	
	// Account Lookup Failures
	'failed_to_find_email'              => "We couldn't find an account associated with that email address.",
	'failed_to_find_phone'              => "We couldn't find an account associated with that phone number.",
	'failed_to_find_username'           => "We couldn't find an account associated with that username.",
	'failed_to_find_login_field'        => "We couldn't find an account matching that login.",
	'failed_login'                      => 'These credentials do not match our records.',
	'invalid_session'                   => 'Invalid session. Please log in again.',
	
	// Account Verification
	'verification_link_sent'            => 'We\'ve sent a verification link to your email <strong>:fieldHiddenValue</strong>. Please check your inbox (and spam folder) to verify your email address.',
	'verification_code_sent'            => 'We\'ve sent a verification code via SMS to <strong>:fieldHiddenValue</strong>. Check your messages to verify your phone number.',
	'resend_verification_link'          => 'Need another verification link? We can resend it to your email address. Click the "Resend" link to proceed.',
	'resend_verification_code'          => 'Didn\'t receive the verification code? We can resend it to your phone number. Click the "Resend" link to get a new code.',
	'resend'                            => 'Resend',
	'need_to_verify_your_email_to_continue' => 'Please verify your email address to continue. We\'ve sent a verification link to your email. Check your inbox (and spam folder) and click the link to complete the process.',
	'need_to_verify_your_phone_to_continue' => 'Please verify your phone number to continue. We\'ve sent a verification code via SMS. Check your messages to proceed.',
	'verification_link_sent_to_user'    => 'A verification link has been sent to the user to confirm their email address.',
	'verification_code_sent_to_user'    => 'A verification code has been sent to the user to confirm their phone number.',
	'entity_id_not_found'               => 'Entity ID not found.',
	'verification_token_or_code_missing' => 'The verification token or code is missing.',
	'field_successfully_verified'       => 'Congratulation :name ! Your :field has been verified.',
	'field_already_verified'            => 'Your :field is already verified.',
	'field_verification_failed'         => 'Your :field verification has failed.',
	'login_to_retrieve_verification_data' => 'Enter your credentials below to retrieve the verification link or code.',
	
	// Code Delivery Messages
	'code_sent_to_email'                => 'We\'ve sent a verification code to your email. Please check your inbox (and spam folder) and enter the code to verify your email address.',
	'code_sent_by_sms'                  => 'We\'ve sent a verification code via SMS. Please check your messages and enter the code to verify your phone number.',
	'code_sent_by'                      => 'We\'ve sent a verification code to your phone number via SMS or to your email address. Please check your messages or inbox and enter the code to verify your account.',
	'field_successfully_verified_token' => 'Your :field has been verified. Now, you can reset your password.',
	
	// Notification Channels
	'notifications_channel'             => 'Notification Channel',
	'notifications_channel_hint'        => 'Select a channel for notifications sending',
	'channel_mail'                      => 'Mail',
	'channel_sms'                       => 'SMS',
	'register_with_email'               => 'Register with email',
	'register_with_phone'               => 'Register with phone',
	'login_with_email'                  => 'Login with email',
	'login_with_phone'                  => 'Login with phone number',
	'use_email'                         => 'Use email',
	'use_phone'                         => 'Use phone number',
	'auth_field_label'                  => 'Main Auth Field',
	'auth_field_hint'                   => 'Select the main authenticate field.',
	
	// Impersonation
	'issue_to_login_in'                 => 'There was an issue logging in. Please try again.',
	
	// Social Login
	'login_with_title'                  => 'Login with',
	'register_with_title'               => 'Register with',
	'or'                                => 'Or',
	'or_login_with'                     => 'or login with',
	'or_register_with'                  => 'or register with',
	'login_with'                        => 'Login with <strong>:provider</strong>',
	'connected_with'                    => 'Connected with <strong>:provider</strong>',
	'social_login_service_not_found'    => 'The social network "%s" is not available.',
	'social_login_service_not_enabled'  => 'The social network "%s" is not enabled.',
	'social_login_service_error'        => 'Unknown error. The service does not work.',
	'social_login_email_not_found'      => 'Email address not found. Your "%s" account cannot be linked to our website.',
	'social_login_unknown_error'        => 'Unknown error. Please try again in a few minutes.',
	'social_login_user_not_saved_error' => 'Unknown error. User data not saved.',
	'account_created_via_social_login' => 'Your account was created via social login. Please use your social provider to log in.',
	'social_media_name_logo'           => 'Logo Only',
	'social_media_name_default'        => 'Logo + Name',
	'social_media_name_login_with'     => 'Logo + Login with + Name',
	
	// Two-Factor Authentication (2FA)
	'two_factor_title'                  => 'Two-Factor Authentication',
	'two_factor_description'            => 'Please enter the OTP (one time password) to verify your account. A Code has been sent to <span class="text-dark">:fieldHiddenValue</span>',
	'two_factor_description_std'        => 'Please enter the OTP (one time password) to verify your account. We\'ve sent you a Code. If you haven\'t received it, use the link below to request another one.',
	'resend_code'                      => 'Resend Code',
	'two_factor_settings_title'        => 'Two-Factor Authentication (2FA) Settings',
	'two_factor_settings_info'         => 'Enabling 2FA will add an extra layer of security to your account. <br>You will need to verify your identity using a one-time password (OTP) sent to your email or phone.',
	'two_factor_method'                => '2FA Method',
	'two_factor_method_hint'           => '2FA Method',
	'two_factor_method_email'          => 'Email',
	'two_factor_method_sms'            => 'SMS',
	'two_factor_status'                => '2FA Status',
	'two_factor_enable'                => 'Enable',
	'two_factor_disable'               => 'Disable',
	'two_factor_enabled'               => 'Enabled',
	'two_factor_disabled'              => 'Disabled',
	'two_factor_was_just_enabled'      => 'Two-factor authentication has been enabled. Please verify your identity.',
	'two_factor_has_been_enabled'      => 'Two-factor authentication has been enabled.',
	'two_factor_has_been_disabled'     => 'Two-factor authentication has been disabled.',
	'expired_otp'                      => 'The two-factor code has expired. Please try again.',
	'invalid_otp'                      => 'The provided two-factor code is invalid.',
	'maximum_otp_resend_attempts_reached' => 'Maximum OTP resend attempts reached. Please try again later.',
	'wait_before_request_new_otp'      => 'Please wait :humanReadableTime seconds before requesting a new code.',
	'otp_sent'                         => 'OTP sent successfully.',
	'new_otp_sent'                     => 'A new OTP has been sent.',
	
	// Account Lock and Suspension
	'account_locked_for_excessive_login_attempts'       => 'Your account is locked due to excessive login attempts. Please try again later or contact support.',
	'account_locked_for_excessive_link_resend_attempts' => 'Your account is locked due to excessive link resend attempts. Please try again later or contact support.',
	'account_locked_for_excessive_otp_resend_attempts'  => 'Your account is locked due to excessive OTP resend attempts. Please try again later or contact support.',
	'account_suspended_due_to'                          => 'Your account has been suspended due to a violation of our Terms of Service. This may be due to activities such as unauthorized access, policy violations, suspicious behavior, or other breaches of our guidelines. If you believe this was a mistake, please contact support for further assistance.',
	'account_banned_due_to'                             => 'Your account has been permanently banned due to a violation of our Terms of Service. As a result, your account has been deleted, and you will not be able to restore or create a new account using the same credentials. If you believe this was a mistake, you may contact support for further assistance in requesting permission to create a new account.',
	
	// Account Status Messages
	'account_restricted'                                => 'Access to this account has been restricted.',
	'account_suspended'                                 => 'This account has been suspended.',
	'account_suspended_help'                            => 'This account has been suspended. For more details or assistance, please contact our support team.',
	'account_suspended_due_to_violation'                => 'This account has been suspended due to a violation of our terms and conditions. For more information, please contact our support team.',
	
	// User Profile and Settings
	'overview'                => 'Overview',
	'profile'                 => 'Profile',
	'security'                => 'Security',
	'connections'             => 'Connections',
	'preferences'             => 'Preferences',
	'linked_accounts'         => 'Linked Accounts',
	'connected_accounts'      => 'Connected External Accounts',
	'connected_accounts_hint' => 'Below is a list of social accounts currently connected to your profile.',
	'no_connected_accounts'   => 'No external account connections available.',
	'disconnect'              => 'Disconnect',
	'connect'                 => 'Connect',
	'service'                 => 'Service',
	
	// Error Messages
	'unauthorized_access'            => 'Oops! It looks like you\'re not authorized to access this account.',
	'unauthorized_access_for_action' => 'Oops! It looks like you\'re not authorized to do this action.',
	'validation_errors_title'        => 'Oops! There were some issues with your submission',
	
	// CAPTCHA
	'captcha_human_verification' => 'Verify you\'re human',
	'captcha_not_robot_proof' => 'Prove you\'re not a robot',
	'captcha_unlike_robots' => 'We don\'t like robots',
	'captcha_placeholder' => 'Enter security code',
	'captcha_completion' => 'Complete the CAPTCHA',
	
	// Notifications
	'communication'           => [
		'email_verification_link'       => [
			'mail' => [
				'subject'          => 'Verify Your Email',
				'greeting'         => 'Hi :userName!',
				'action_info'      => 'Click below to verify your email address.',
				'action_text'      => 'Verify Email Address',
				'body'             => 'Your verification code is: <strong>:token</strong>',
				'footer_info'      => 'You\'re receiving this email because you recently created a :appName account or updated your email address.',
				'footer_no_action' => 'If this wasn\'t you, please ignore this email.',
			],
			'sms'  => '',
		],
		'field_verification_code'       => [
			'mail' => [
				'subject'          => 'Verify Your Email',
				'greeting'         => 'Hi :userName!',
				'body'             => 'Your verification code is: <strong>:token</strong>',
				'action_info'      => 'Copy and paste it into the OTP form or click below to verify your email address.',
				'action_text'      => 'Verify Email Address',
				'footer_info'      => 'You\'re receiving this email because you recently created a :appName account or updated your email address.',
				'footer_no_action' => 'If this wasn\'t you, please ignore this email.',
			],
			'sms'  => ':appName - Verify your phone number with the OTP code: :token',
		],
		'reset_password_link'           => [
			'mail' => [
				'subject'          => 'Reset Your Password',
				'greeting'         => 'Hello!',
				'action_info'      => 'Click below to reset your password.',
				'action_text'      => 'Reset Password',
				'expiration_info'  => 'This link expires in :expireTimeString.',
				'footer_info'      => 'You\'re receiving this email because a password reset was requested for your account.',
				'footer_no_action' => 'If you didn\'t request this, please secure your account immediately.',
			],
			'sms'  => '',
		],
		'reset_password_code'           => [
			'mail' => [
				'subject'          => 'Reset Your Password',
				'greeting'         => 'Hello!',
				'body'             => 'Your password reset code is: <strong>:token</strong>',
				'action_info'      => 'Copy and paste it into the OTP form or click below to verify your account.',
				'action_text'      => 'Verify Your Account',
				'expiration_info'  => 'This code expires in :expireTimeString.',
				'footer_info'      => 'You\'re receiving this email because a password reset was requested for your account.',
				'footer_no_action' => 'If you didn\'t request this, please secure your account immediately.',
			],
			'sms'  => ':appName - Reset password with the OTP code: :token. Expires in: :expireTimeString',
		],
		'account_created_with_password' => [
			'mail' => [
				'subject'          => 'Your Account Password',
				'greeting'         => 'Hello :userName!',
				'body'             => 'Your account has been created successfully.',
				'action_info'      => 'Click the button below to verify your email address.',
				'action_text'      => 'Verify Email Address',
				'password_info'    => 'Your temporary password is: <strong>:generatedPassword</strong>',
				'login_prompt'     => 'Log in now to get started!',
				'footer_info'      => 'You\'re receiving this email because you recently created a new :appName account or added a new email address.',
				'footer_no_action' => 'If this wasn\'t you, please ignore this email.',
			],
			'sms'  => ':appName - Your password: :generatedPassword. Verify your phone number with the OTP code: :token',
		],
		'account_created'               => [
			'mail' => [
				'subject'          => 'Welcome to :appName!',
				'greeting'         => 'Welcome, :userName!',
				'body'             => 'Your :appName account has been created.',
				'security_notes'   => '<strong>Security Tips:</strong><br>
<br>1 - Use a strong, unique password for your account.
<br>2 - Be cautious of suspicious emails, messages, or links.
<br>3 - Never share your login credentials with anyone.
<br>4 - Regularly review your account activity for any unusual access.
<br><br>If you notice anything suspicious or need assistance, please contact our support team immediately.',
				'footer_info'      => 'You\'re receiving this email because you recently created a new :appName account.',
				'footer_no_action' => 'If this wasn\'t you, please ignore this email.',
			],
			'sms'  => 'Welcome :userName! Your :appName account has been created.',
		],
		'account_activated'             => [
			'mail' => [
				'subject'          => 'Welcome to :appName!',
				'greeting'         => 'Welcome, :userName!',
				'body'             => 'Your :appName account has been activated.',
				'security_notes'   => '<strong>Security Tips:</strong><br>
<br>1 - Use a strong, unique password for your account.
<br>2 - Be cautious of suspicious emails, messages, or links.
<br>3 - Never share your login credentials with anyone.
<br>4 - Regularly review your account activity for any unusual access.
<br><br>If you notice anything suspicious or need assistance, please contact our support team immediately.',
				'footer_info'      => 'You\'re receiving this email because you recently created a new :appName account.',
				'footer_no_action' => 'If this wasn\'t you, please ignore this email.',
			],
			'sms'  => 'Welcome :userName! Your :appName account is now activated.',
		],
		'user_registered'               => [
			'mail' => [
				'subject'  => 'New User Registration',
				'greeting' => 'Hello Admin,',
				'body'     => ':name has just registered.',
				'details'  => 'Registered on: :now at :time<br>' .
					'Auth field: :authField<br>' .
					'Email: :email<br>' .
					'Phone: :phone',
			],
			'sms'  => '',
		],
		'account_deleted'               => [
			'mail' => [
				'subject'     => 'Your :appName Account Has Been Deleted',
				'greeting'    => 'Hello,',
				'body'        => 'Your account was deleted from <a href=":appUrl">:appName</a> on :now.',
				'closing'     => 'Thank you for your trust. We hope to see you again soon.',
				'footer_info' => 'This is an automated email—please don\'t reply.',
			],
			'sms'  => ':appName - Your account has been deleted.',
		],
		'two_factor_recommendation'     => [
			'mail' => [
				'subject'          => 'Set Up Two-Factor Authentication',
				'greeting'         => 'Hello :userName!',
				'body'             => 'Enhance your account security by enabling Two-Factor Authentication (2FA).',
				'action_text'      => 'Enable 2FA',
				'action_info'      => 'Click below to set up 2FA for your account.',
				'footer_info'      => 'You\'re receiving this email because 2FA is available for your :appName account.',
				'footer_no_action' => 'No action is required if you prefer to keep your current settings as is.',
			],
			'sms'  => ':appName - Enable 2FA for extra security. Visit your account settings.',
		],
		'two_factor_enabled'            => [
			'mail' => [
				'subject'      => 'Two-Factor Authentication Enabled',
				'greeting'     => 'Hi :userName,',
				'body'         => 'Two-factor authentication has been successfully enabled on your account.',
				'security_tip' => 'If you did not enable this, please secure your account immediately.',
			],
			'sms'  => ':appName - Two-factor authentication has been enabled for your account.',
		],
		'two_factor_disabled'           => [
			'mail' => [
				'subject'      => 'Two-Factor Authentication Disabled',
				'greeting'     => 'Hi :userName,',
				'body'         => 'Two-factor authentication has been disabled on your account.',
				'security_tip' => 'If you did not request this, please secure your account immediately.',
			],
			'sms'  => ':appName - Two-factor authentication has been disabled on your account.',
		],
		'two_factor_verification'       => [
			'mail' => [
				'subject'          => 'Your Two-Factor Authentication Code',
				'greeting'         => 'Hello :userName!',
				'body'             => 'Your Two-Factor Authentication code is: <strong>:code</strong>',
				'expiration_info'  => 'This code will expire in :expireTimeString.',
				'footer_info'      => 'You\'re receiving this email because you\'re logging in with 2FA enabled.',
				'footer_no_action' => 'If you did not request this code, please secure your account immediately.',
			],
			'sms'  => ':appName - Your 2FA code is: :code. Expires in: :expireTimeString',
		],
		'new_login'                     => [
			'mail' => [
				'subject'        => 'New Login Detected',
				'greeting'       => 'Hi :userName,',
				'new_login_info' => 'A new login was detected on your account from IP: :ipAddress at :timeString.',
				'security_tip'   => 'If this wasn\'t you, please secure your account immediately.',
			],
			'sms'  => ':appName - A new login was detected on your account from IP: :ipAddress at :timeString.',
		],
		'session_terminated'            => [
			'mail' => [
				'subject'            => 'Your Session Has Been Terminated',
				'greeting'           => 'Hi :userName,',
				'session_terminated' => 'A session has been logged out from your account.',
				'security_tip'       => 'If this wasn\'t you, please secure your account immediately.',
			],
			'sms'  => ':appName - A session has been logged out from your account.',
		],
		'account_locked'                => [
			'mail' => [
				'subject'             => 'Your Account Has Been Locked',
				'greeting'            => 'Hello :userName!',
				'body'                => 'Your account has been locked due to multiple failed login attempts.',
				'unlock_instructions' => 'To unlock your account, please click the button below.',
				'action_text'         => 'Unlock Account',
				'footer_info'         => 'You\'re receiving this email because your :appName account has been locked.',
				'footer_no_action'    => 'If this wasn\'t you, please contact us immediately.',
			],
			'sms'  => ':appName - Your account has been locked due to multiple failed login attempts. Unlock code: :unlockCode',
		],
		'account_suspended'             => [
			'mail' => [
				'subject'     => 'Your Account Has Been Suspended',
				'greeting'    => 'Hello :userName!',
				'body'        => 'Your account has been suspended due to a violation of our Terms of Service. This may be due to activities such as unauthorized access, policy violations, suspicious behavior, or other breaches of our guidelines.',
				'action_info' => 'Click below to appeal this decision or get more information.',
				'action_text' => 'Contact Support',
				'footer_info' => 'You\'re receiving this email because of an issue with your :appName account.',
			],
			'sms'  => ':appName - Your account is suspended due to a violation of our Terms of Service. Contact support.',
		],
		'account_banned'                => [
			'mail' => [
				'subject'  => 'Your Account Has Been Banned',
				'greeting' => 'Hello :userName!',
				'body'     => 'Your account has been permanently banned due to a violation of our Terms of Service.',
			],
			'sms'  => ':appName - Your account has been banned due to a violation of our Terms of Service.',
		],
		'password_changed'              => [
			'mail' => [
				'subject'          => 'Your Password Has Been Updated',
				'greeting'         => 'Hello :userName!',
				'body'             => 'Your password was successfully changed on :now.',
				'footer_info'      => 'You\'re receiving this email to confirm a password change on your :appName account.',
				'footer_no_action' => 'If this wasn\'t you, contact us immediately.',
			],
			'sms'  => ':appName - Your password was changed on :now.',
		],
		'account_updated'               => [
			'mail' => [
				'subject'          => 'Your Account Has Been Updated',
				'greeting'         => 'Hello :userName!',
				'body'             => 'Your account details have been updated.',
				'updated_fields'   => 'Updated Fields: :updatedFields',
				'footer_info'      => 'You\'re receiving this email because your account details were updated.',
				'footer_no_action' => 'If this wasn\'t you, please review your account settings immediately.',
			],
			'sms'  => ':appName - Your account details have been updated. Updated fields: :updatedFields',
		],
	],

];
