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

namespace App\Http\Controllers\Web\Admin;

use App\Enums\Gender;
use App\Helpers\Common\Date\TimeZoneManager;
use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Controllers\Web\Admin\Traits\UserTrait;
use App\Http\Requests\Admin\Request;
use App\Http\Requests\Admin\UserRequest as StoreRequest;
use App\Http\Requests\Admin\UserRequest as UpdateRequest;
use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserController extends PanelController
{
	use UserTrait;
	
	public function setup()
	{
		$onAccountPage = routeActionHas('@account');
		$this->onEditPage = (routeActionHas('@edit') || $onAccountPage);
		
		$authUser = auth()->user();
		if (empty($authUser)) {
			abort(Response::HTTP_FORBIDDEN, 'Not allowed.');
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(User::class);
		$this->xPanel->with([
			'permissions',
			'roles',
			'country:code,name',
			// IMPORTANT:
			// Payable can have multiple on-hold, pending, expired, canceled or refunded payments
			// Payable can have only one valid payment, so we have to add that as filter for the Eager Loading.
			// This allows displaying the right payment status (So only when payment is valid).
			'payment' => fn ($query) => $query->valid(),
			'payment.package:id,type,name,short_name',
		]);
		$this->xPanel->withoutAppends();
		
		// If the logged admin user has permissions to manage users and has not 'super-admin' role,
		// don't allow him to manage 'super-admin' role's users.
		if (!doesUserHaveSuperAdminPermission($authUser)) {
			// Get 'super-admin' role's users IDs
			$usersIds = [];
			try {
				$users = User::query()
					->withoutGlobalScopes([VerifiedScope::class])
					->role(Role::getSuperAdminRole())
					->get(['id', 'created_at']);
				if ($users->count() > 0) {
					$usersIds = $users->keyBy('id')->keys()->toArray();
				}
			} catch (Throwable $e) {
			}
			
			// Exclude 'super-admin' role's users from list
			if (!empty($usersIds)) {
				$this->xPanel->addClause('whereNotIn', 'id', $usersIds);
			}
		}
		
		$this->xPanel->setRoute(urlGen()->adminUri('users'));
		$this->xPanel->setEntityNameStrings(trans('admin.user'), trans('admin.users'));
		if (!request()->input('order')) {
			$this->xPanel->orderByDesc('created_at');
		}
		$this->xPanel->enableDetailsRow();
		$this->xPanel->setDetailsRowView('admin.panel.details_row.users');
		$this->xPanel->allowAccess(['details_row']);
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		$this->xPanel->addButtonFromModelFunction('line', 'impersonate', 'impersonateInLineButton', 'beginning');
		$this->xPanel->removeButton('delete');
		$this->xPanel->addButtonFromModelFunction('line', 'delete', 'deleteInLineButton', 'end');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// FILTERS
			$this->xPanel->addFilter(
				options: [
					'name'  => 'id',
					'type'  => 'text',
					'label' => 'ID',
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', 'id', '=', $value);
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'from_to',
					'type'  => 'date_range',
					'label' => trans('admin.Date range'),
				],
				filterLogic: function ($value) {
					$dates = json_decode($value);
					$this->xPanel->addClause('where', 'created_at', '>=', $dates->from);
					$this->xPanel->addClause('where', 'created_at', '<=', $dates->to);
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'name',
					'type'  => 'text',
					'label' => trans('admin.Name'),
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', function ($query) use ($value) {
						$query->where('name', 'LIKE', "%$value%");
					});
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'username',
					'type'  => 'text',
					'label' => trans('auth.username'),
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', function ($query) use ($value) {
						$query->where('username', 'LIKE', "%$value%");
					});
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'email',
					'type'  => 'text',
					'label' => trans('auth.email'),
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', function ($query) use ($value) {
						$query->where('email', 'LIKE', "%$value%");
					});
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'country',
					'type'  => 'select2',
					'label' => mb_ucfirst(trans('admin.country')),
				],
				values: getCountries(),
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', 'country_code', '=', $value);
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'has_subscription',
					'type'  => 'dropdown',
					'label' => trans('admin.has_subscription'),
				],
				values: [
					'pending' => t('pending'),
					'onHold'  => t('onHold'),
					'valid'   => t('valid'),
					'expired' => t('expired'),
					// 'canceled' => t('canceled'),
					// 'refunded' => t('refunded'),
				],
				filterLogic: function ($value) {
					if ($value == 'pending') {
						$this->xPanel->addClause('whereHas', 'payment', function ($query) {
							$query->where(function ($query) {
								$query->valid()->orWhere(fn ($query) => $query->onHold());
							})->columnIsEmpty('active');
						});
					}
					if ($value == 'onHold') {
						$this->xPanel->addClause('whereHas', 'payment', function ($query) {
							$query->onHold()->active();
						});
					}
					if ($value == 'valid') {
						$this->xPanel->addClause('whereHas', 'payment', function ($query) {
							$query->valid()->active();
						});
					}
					if ($value == 'expired') {
						$this->xPanel->addClause('whereHas', 'payment', function ($query) {
							$query->where(function ($query) {
								$query->notValid()->where(fn ($query) => $query->columnIsEmpty('active'));
							})->orWhere(fn ($query) => $query->notValid());
						});
					}
					if ($value == 'canceled') {
						$this->xPanel->addClause('whereHas', 'payment', fn ($query) => $query->canceled());
					}
					if ($value == 'refunded') {
						$this->xPanel->addClause('whereHas', 'payment', fn ($query) => $query->refunded());
					}
				}
			);
			
			if (config('plugins.offlinepayment.installed')) {
				$this->xPanel->addFilter(
					options: [
						'name'  => 'has_valid_subscription',
						'type'  => 'dropdown',
						'label' => trans('admin.has_valid_subscription'),
					],
					values: [
						'real' => trans('admin.with_real_payment'),
						'fake' => trans('admin.with_fake_payment'),
					],
					filterLogic: function ($value) {
						if ($value == 'real') {
							$this->xPanel->addClause('whereHas', 'payment', function ($query) {
								$query->valid()->active()->notManuallyCreated();
							});
						}
						if ($value == 'fake') {
							$this->xPanel->addClause('whereHas', 'payment', function ($query) {
								$query->valid()->active()->manuallyCreated();
							});
						}
					}
				);
			}
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'status',
					'type'  => 'dropdown',
					'label' => trans('admin.Status'),
				],
				values: [
					1 => trans('admin.Unactivated'),
					2 => trans('admin.Activated'),
					3 => trans('admin.locked'),
					4 => trans('admin.suspended'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', fn (Builder $query) => $query->unverified());
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', fn (Builder $query) => $query->verified());
					}
					if ($value == 3) {
						$this->xPanel->addClause('where', fn (Builder $query) => $query->whereNotNull('locked_at'));
					}
					if ($value == 4) {
						$this->xPanel->addClause('where', fn (Builder $query) => $query->whereNotNull('suspended_at'));
					}
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'type',
					'type'  => 'dropdown',
					'label' => trans('admin.permissions_roles'),
				],
				values: [
					1 => trans('admin.has_admins_permissions'),
					2 => trans('admin.has_super_admins_permissions'),
					3 => trans('admin.has_super_admins_role'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('permission', Permission::getStaffPermissions());
					}
					if ($value == 2) {
						$this->xPanel->addClause('permission', Permission::getSuperAdminPermissions());
					}
					if ($value == 3) {
						$this->xPanel->addClause('role', Role::getSuperAdminRole());
					}
				}
			);
			
			$isPhoneVerificationEnabled = (config('settings.sms.phone_verification') == 1);
			
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'created_at',
				'label' => trans('admin.Date'),
				'type'  => 'datetime',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'name',
				'label'         => trans('admin.Name'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
			]);
			
			$emailParams = [
				'name'  => 'email',
				'label' => trans('admin.Email'),
			];
			if ($isPhoneVerificationEnabled) {
				$emailParams['type'] = 'model_function';
				$emailParams['function_name'] = 'crudEmailColumn';
			}
			$this->xPanel->addColumn($emailParams);
			
			if ($isPhoneVerificationEnabled) {
				$this->xPanel->addColumn([
					'name'          => 'phone',
					'label'         => mb_ucfirst(t('phone')),
					'type'          => 'model_function',
					'function_name' => 'crudPhoneColumn',
				]);
			}
			
			$this->xPanel->addColumn([
				'label'         => mb_ucfirst(trans('admin.country')),
				'name'          => 'country_code',
				'type'          => 'model_function',
				'function_name' => 'crudCountryColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'email_verified_at',
				'label'         => trans('admin.Verified Email'),
				'type'          => 'model_function',
				'function_name' => 'crudVerifiedEmailColumn',
			]);
			
			if ($isPhoneVerificationEnabled) {
				$this->xPanel->addColumn([
					'name'          => 'phone_verified_at',
					'label'         => trans('admin.Verified Phone'),
					'type'          => 'model_function',
					'function_name' => 'crudVerifiedPhoneColumn',
				]);
			}
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$user = null;
			
			if ($this->onEditPage) {
				$currentResourceId = request()->route()->parameter('user');
				$dealingUserId = $onAccountPage ? $authUser->getAuthIdentifier() : $currentResourceId;
				
				$user = User::query()->with(['permissions', 'roles'])->find($dealingUserId);
			}
			
			$defaultPicture = config('larapen.media.avatar');
			$diskName = 'public';
			$defaultPictureUrl = Storage::disk($diskName)->url($defaultPicture);
			$this->xPanel->addField([
				'name'    => 'photo_path',
				'label'   => t('Photo or Avatar'),
				'type'    => 'image',
				'upload'  => true,
				'default' => $defaultPictureUrl,
				'disk'    => $diskName,
				'width'   => 300,
				'hint'    => t('file_types', ['file_types' => getAllowedFileFormatsHint('image')]),
			]);
			
			$this->xPanel->addField([
				'label'       => trans('admin.Gender'),
				'name'        => 'gender_id',
				'type'        => 'select2_from_array',
				'options'     => $this->genders(),
				'allows_null' => false,
				'wrapper'     => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'name',
				'label'      => trans('admin.Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Name'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'        => 'auth_field',
				'label'       => trans('auth.auth_field_label'),
				'type'        => 'select2_from_array',
				'options'     => getAuthFields(),
				'allows_null' => true,
				'default'     => getAuthField($user),
				'hint'        => trans('auth.auth_field_hint'),
				'wrapper'     => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'username',
				'label'      => trans('auth.username'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('auth.username'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'email',
				'label'      => trans('admin.Email'),
				'type'       => 'email',
				'attributes' => [
					'placeholder' => trans('admin.Email'),
				],
				'prefix'     => '<i class="fa-regular fa-envelope"></i>',
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$formForPwd = $onAccountPage ? 'update' : 'create';
			$this->xPanel->addField([
				'name'       => 'password',
				'label'      => trans('admin.Password'),
				'type'       => 'password',
				'attributes' => [
					'placeholder'  => trans('admin.Password'),
					'autocomplete' => 'new-password',
				],
				'prefix'     => '<i class="fa-solid fa-lock"></i>',
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			], $formForPwd);
			
			$phoneCountry = (!empty($user) && isset($user->phone_country)) ? strtolower($user->phone_country) : 'us';
			$this->xPanel->addField([
				'name'          => 'phone',
				'label'         => trans('admin.Phone'),
				'type'          => 'intl_tel_input',
				'phone_country' => $phoneCountry,
				'wrapper'       => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'phone_hidden',
				'label'   => trans('admin.Phone hidden'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6 mt-4',
				],
			]);
			
			$this->xPanel->addField([
				'label'     => mb_ucfirst(trans('admin.country')),
				'name'      => 'country_code',
				'model'     => Country::class,
				'entity'    => 'country',
				'attribute' => 'name',
				'type'      => 'select2',
				'wrapper'   => [
					'class' => 'col-md-6',
				],
			]);
			
			$timezoneHint = $onAccountPage
				? t('admin_preferred_time_zone_info_lite')
				: t('preferred_time_zone_info_lite');
			$this->xPanel->addField([
				'name'        => 'time_zone',
				'label'       => t('preferred_time_zone_label'),
				'type'        => 'select2_from_array',
				'options'     => TimeZoneManager::getTimeZones(),
				'allows_null' => true,
				'hint'        => $timezoneHint,
				'wrapper'     => [
					'class' => 'col-md-6',
				],
			]);
			
			$lastLoginAtLabel = trans('admin.last_login_at_label');
			$lastLoginAtFormatted = $user->last_login_at ?? null;
			$this->xPanel->addField([
				'name'    => 'last_login_at',
				'type'    => 'custom_html',
				'value'   => '<br><h5 class="mt-3"><span class="fw-bold">' . $lastLoginAtLabel . ':</span> ' . $lastLoginAtFormatted . '</h5>',
				'wrapper' => [
					'class' => 'col-md-6',
				],
			], 'update');
			
			if (!$onAccountPage) {
				$this->xPanel->addField([
					'name'    => 'email_verified_at',
					'label'   => trans('admin.Verified Email'),
					'type'    => 'checkbox_switch',
					'wrapper' => [
						'class' => 'col-md-6',
					],
				]);
				
				$this->xPanel->addField([
					'name'    => 'phone_verified_at',
					'label'   => trans('admin.Verified Phone'),
					'type'    => 'checkbox_switch',
					'wrapper' => [
						'class' => 'col-md-6',
					],
				]);
				
				$this->xPanel->addField([
					'name'    => 'locked_at',
					'label'   => trans('admin.locked'),
					'type'    => 'checkbox_switch',
					'wrapper' => [
						'class' => 'col-md-6',
					],
					'hint'    => trans('admin.locked_hint'),
				]);
				
				$this->xPanel->addField([
					'name'    => 'suspended_at',
					'label'   => trans('admin.suspended'),
					'type'    => 'checkbox_switch',
					'wrapper' => [
						'class' => 'col-md-6',
					],
					'hint'    => trans('admin.suspended_hint'),
				]);
				
				if (!empty($user)) {
					$this->xPanel->addField([
						'name'  => 'ip_separator',
						'type'  => 'custom_html',
						'value' => '<hr style="opacity: 0.15">',
					], 'update');
					
					$emptyIp = 'N/A';
					
					$label = '<span class="fw-bold">' . trans('admin.create_from_ip') . ':</span>';
					if (!empty($user->create_from_ip)) {
						$ipUrl = config('larapen.core.ipLinkBase') . $user->create_from_ip;
						$ipLink = '<a href="' . $ipUrl . '" target="_blank">' . $user->create_from_ip . '</a>';
					} else {
						$ipLink = $emptyIp;
					}
					$this->xPanel->addField([
						'name'    => 'create_from_ip',
						'type'    => 'custom_html',
						'value'   => '<h5>' . $label . ' ' . $ipLink . '</h5>',
						'wrapper' => [
							'class' => 'col-md-6',
						],
					], 'update');
					
					$label = '<span class="fw-bold">' . trans('admin.latest_update_ip') . ':</span>';
					if (!empty($user->latest_update_ip)) {
						$ipUrl = config('larapen.core.ipLinkBase') . $user->latest_update_ip;
						$ipLink = '<a href="' . $ipUrl . '" target="_blank">' . $user->latest_update_ip . '</a>';
					} else {
						$ipLink = $emptyIp;
					}
					$this->xPanel->addField([
						'name'    => 'latest_update_ip',
						'type'    => 'custom_html',
						'value'   => '<h5>' . $label . ' ' . $ipLink . '</h5>',
						'wrapper' => [
							'class' => 'col-md-6',
						],
						'newline' => true,
					], 'update');
					
					if (!empty($user->email) || !empty($user->phone)) {
						$this->xPanel->addField([
							'name'  => 'ban_separator',
							'type'  => 'custom_html',
							'value' => '<hr style="opacity: 0.15">',
						], 'update');
						
						$btnUrl = urlGen()->adminUrl('blacklists/add');
						$queryParams = [];
						if (!empty($user->email)) {
							$queryParams['email'] = $user->email;
						}
						if (!empty($user->phone)) {
							$queryParams['phone'] = $user->phone;
						}
						$btnUrl = urlBuilder($btnUrl)->setParameters($queryParams)->toString();
						
						$btnText = trans('admin.ban_the_user');
						$btnHint = $btnText;
						if (!empty($user->email) && !empty($user->phone)) {
							$btnHint = trans('admin.ban_the_user_email_and_phone', [
								'email' => $user->email,
								'phone' => $user->phone,
							]);
						} else {
							if (!empty($user->email)) {
								$btnHint = trans('admin.ban_the_user_email', ['email' => $user->email]);
							}
							if (!empty($user->phone)) {
								$btnHint = trans('admin.ban_the_user_phone', ['phone' => $user->phone]);
							}
						}
						$tooltip = ' data-bs-toggle="tooltip" title="' . $btnHint . '"';
						
						$btnLink = '<a href="' . $btnUrl . '" class="btn btn-danger confirm-simple-action"' . $tooltip . '>' . $btnText . '</a>';
						$this->xPanel->addField([
							'name'    => 'ban_button',
							'type'    => 'custom_html',
							'value'   => $btnLink,
							'wrapper' => [
								'style' => 'text-align:center;',
							],
						], 'update');
					}
				}
				
				/*
				 * Only 'super-admin' can assign 'roles' or 'permissions' to users
				 * Also logged-in admin user cannot manage his own 'role' or 'permissions'
				 */
				$isSuperAdmin = doesUserHaveSuperAdminPermission($authUser);
				$isCurrentUser = (!empty($user) && $user->id == $authUser->getAuthIdentifier());
				$hasUserManagementAccess = ($isSuperAdmin && ($this->onCreatePage || ($this->onEditPage && !$isCurrentUser)));
				
				if ($hasUserManagementAccess) {
					$this->xPanel->addField([
						'name'  => 'acl_separator',
						'type'  => 'custom_html',
						'value' => '<hr style="opacity: 0.25">',
					]);
					
					$this->xPanel->addField([
						// Two interconnected entities
						'label'             => trans('admin.user_role_permission'),
						'field_unique_name' => 'user_role_permission',
						'type'              => 'checklist_dependency',
						'name'              => 'roles_and_permissions', // the methods that defines the relationship in your Model
						'subfields'         => [
							'primary'   => [
								'label'            => trans('admin.roles'),
								'name'             => 'roles', // the method that defines the relationship in your Model
								'entity'           => 'roles', // the method that defines the relationship in your Model
								'entity_secondary' => 'permissions', // the method that defines the relationship in your Model
								'attribute'        => 'name', // foreign key attribute that is shown to user
								'model'            => config('permission.models.role'), // foreign key model
								'pivot'            => true, // on create&update, do you need to add/delete pivot table entries?
								'number_columns'   => 3, // can be 1,2,3,4,6
							],
							'secondary' => [
								'label'          => mb_ucfirst(trans('admin.permission_singular')),
								'name'           => 'permissions', // the method that defines the relationship in your Model
								'entity'         => 'permissions', // the method that defines the relationship in your Model
								'entity_primary' => 'roles', // the method that defines the relationship in your Model
								'attribute'      => 'name', // foreign key attribute that is shown to user
								'model'          => config('permission.models.permission'), // foreign key model
								'pivot'          => true, // on create&update, do you need to add/delete pivot table entries?
								'number_columns' => 3, // can be 1,2,3,4,6
							],
						],
					]);
				}
			}
		}
	}
	
	/**
	 * @return \Illuminate\View\View
	 */
	public function account()
	{
		$authUser = auth()->check() ? auth()->user() : null;
		if (empty($authUser)) {
			abort(Response::HTTP_FORBIDDEN, 'Not allowed.');
		}
		
		return parent::edit($authUser->getAuthIdentifier());
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		$request = $this->handleInput($request);
		
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		$currentResourceId = request()->route()->parameter('user');
		
		$request = $this->handleInput($request);
		$request = $this->uploadPhoto($request);
		
		$authUser = auth()->user();
		
		// Is the admin user's own account?
		// If from self account form?
		$isTheAdminOwnAccount = ($authUser->getAuthIdentifier() == $currentResourceId);
		$isFromSelfAccountForm = str_contains(url()->previous(), urlGen()->adminUri('account'));
		
		// Prevent user's role removal
		if ($isTheAdminOwnAccount || $isFromSelfAccountForm) {
			$this->xPanel->disableSyncPivot();
		}
		
		return parent::updateCrud($request);
	}
	
	// PRIVATE METHODS
	
	/**
	 * Handle Input values
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	private function handleInput(Request $request): Request
	{
		// Handle password input fields
		// Remove 'password_confirmation' field & Encrypt password if specified
		$request->request->remove('password_confirmation');
		if ($request->filled('password')) {
			$request->request->set('password', Hash::make($request->input('password')));
		} else {
			$request->request->remove('password');
		}
		
		// Handel 'is_admin' input
		$request = $this->handleIsAdminFromRoles($request);
		$request = $this->handleIsAdminFromPermissions($request);
		
		// Unlock lockout user
		if (!$request->filled('locked_at')) {
			if ($request->has('locked_at')) {
				// Reset lockout-related counters
				$request->request->set('total_login_attempts', 0);
				$request->request->set('otp_resend_attempts', 0);
				$request->request->set('otp_resend_attempts_expires_at', null);
				$request->request->set('total_otp_resend_attempts', 0);
				
				// Reset the two-factor authentication code and expiration
				$request->request->set('two_factor_otp', null);
				$request->request->set('otp_expires_at', null);
				$request->request->set('last_otp_sent_at', null);
			}
		} else {
			// A user cannot be manually locked out,
			// as the error message displayed is specifically related to exceeding the maximum login attempts.
			// Instead, an admin can suspend the user to achieve a similar effect.
			$request->request->set('locked_at', null);
			
			$message = trans('admin.manual_user_lockout_info');
			notification($message, 'info');
		}
		
		return $request;
	}
	
	/**
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	private function uploadPhoto(Request $request): Request
	{
		$user = null;
		
		// From edit page
		$currentResourceId = request()->route()->parameter('user');
		if (!empty($currentResourceId)) {
			$user = User::find($currentResourceId);
		}
		
		// From create page
		if (empty($user)) {
			$userId = $request->input('user_id');
			if (!empty($userId)) {
				$user = User::find($userId);
			}
		}
		
		if (!empty($user)) {
			$attribute = 'photo_path';
			$file = $request->hasFile($attribute) ? $request->file($attribute) : $request->input($attribute);
			
			if (!empty($file)) {
				$param = [
					'destPath' => 'avatars/' . strtolower($user->country_code) . '/' . $user->id,
					'width'    => (int)config('larapen.media.resize.namedOptions.avatar.width', 800),
					'height'   => (int)config('larapen.media.resize.namedOptions.avatar.height', 800),
					'ratio'    => config('larapen.media.resize.namedOptions.avatar.ratio', '1'),
					'upsize'   => config('larapen.media.resize.namedOptions.avatar.upsize', '0'),
				];
				try {
					$photoPath = Upload::image($file, $param['destPath'], $param);
					$request->request->set($attribute, $photoPath);
				} catch (Throwable $e) {
				}
			}
		}
		
		return $request;
	}
	
	/**
	 * @return array
	 */
	private function genders(): array
	{
		$genders = Gender::all();
		
		return collect($genders)->pluck('title', 'id')->toArray();
	}
}
