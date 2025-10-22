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

use App\Enums\BootstrapColor;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\PackageRequest as StoreRequest;
use App\Http\Requests\Admin\PackageRequest as UpdateRequest;
use App\Models\Currency;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;

class PackageController extends PanelController
{
	protected bool $isPromoPackage = false;
	protected bool $isSubsPackage = false;
	
	public function setup()
	{
		$type = request()->segment(3);
		$this->isPromoPackage = ($type == 'promotion');
		$this->isSubsPackage = ($type == 'subscription');
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Package::class);
		$this->xPanel->addClause('where', fn ($query) => $query->$type());
		$this->xPanel->setRoute(urlGen()->adminUri('packages/' . $type));
		if ($this->isPromoPackage) {
			$this->xPanel->setEntityNameStrings(trans('admin.promotion_package'), trans('admin.promotion_packages'));
		}
		if ($this->isSubsPackage) {
			$this->xPanel->setEntityNameStrings(trans('admin.subscription_package'), trans('admin.subscription_packages'));
		}
		$this->xPanel->enableReorder('name', 1);
		$this->xPanel->allowAccess(['reorder']);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// FILTERS
			$this->xPanel->disableSearchBar();
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'name',
					'type'  => 'text',
					'label' => mb_ucfirst(trans('admin.Name')),
				],
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', function ($query) use ($value) {
						$query->where('name', 'LIKE', "%$value%")
							->orWhere('short_name', 'LIKE', "%$value%");
					});
				}
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'status',
					'type'  => 'dropdown',
					'label' => trans('admin.Status'),
				],
				values: [
					1 => trans('admin.Activated'),
					2 => trans('admin.Unactivated'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', 'active', '=', 1);
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
					}
				}
			);
			
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'name',
				'label'         => trans('admin.Name'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'price',
				'label' => trans('admin.Price'),
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'currency_code',
				'label' => trans('admin.Currency'),
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('admin.Active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
				'on_display'    => 'checkbox',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$this->xPanel->addField([
				'name'    => 'type',
				'type'    => 'hidden',
				'default' => $type,
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
				'name'       => 'short_name',
				'label'      => trans('admin.short_name_label'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.short_name_label'),
				],
				'hint'       => trans('admin.short_name_hint_detailed'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			if ($this->isPromoPackage) {
				// Get Bootstrap's Badge Colors
				$badgeColors = BootstrapColor::Badge->colors();
				
				$colorsByName = collect($badgeColors)
					->map(fn ($item, $key) => str($key)->headline()->toString())
					->toArray();
				
				$formattedColors = collect($badgeColors)
					->map(function ($item, $key) {
						$name = str($key)->headline()->toString();
						$color = $item['value'] ?? '';
						
						return ['name' => $name, 'color' => $color];
					})->toArray();
				
				$this->xPanel->addField([
					'name'        => 'ribbon',
					'label'       => trans('admin.Ribbon'),
					'type'        => 'select2_from_skins',
					'options'     => $colorsByName,
					'skins'       => json_encode($formattedColors),
					'allows_null' => true,
					'hint'        => trans('admin.Show listings with ribbon when viewing listings in search results list'),
					'wrapper'     => [
						'class' => 'col-md-6',
					],
				]);
				
				$this->xPanel->addField([
					'name'    => 'has_badge',
					'label'   => trans('admin.Show listings with a badge'),
					'type'    => 'checkbox_switch',
					'hint'    => '<br><br>',
					'wrapper' => [
						'class' => 'col-md-6 mt-4',
					],
				]);
			}
			
			$this->xPanel->addField([
				'name'       => 'price',
				'label'      => trans('admin.Price'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Price'),
				],
				'hint'       => trans('admin.package_price_hint'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'label'     => trans('admin.Currency'),
				'name'      => 'currency_code',
				'model'     => Currency::class,
				'entity'    => 'currency',
				'attribute' => 'code',
				'type'      => 'select2',
				'wrapper'   => [
					'class' => 'col-md-6',
				],
			]);
			
			if ($this->isPromoPackage) {
				$this->xPanel->addField([
					'name'       => 'promotion_time',
					'label'      => trans('admin.promotion_time'),
					'type'       => 'number',
					'attributes' => [
						'placeholder' => trans('admin.promotion_time_in_days'),
						'min'         => 0,
						'step'        => 1,
					],
					'hint'       => trans('admin.promotion_time_hint'),
					'wrapper'    => [
						'class' => 'col-md-6',
					],
				]);
			}
			
			if ($this->isSubsPackage) {
				$this->xPanel->addField([
					'name'        => 'interval',
					'label'       => trans('admin.interval_label'),
					'type'        => 'select2_from_array',
					'options'     => $this->getIntervalOptions(),
					'allows_null' => true,
					'default'     => 'month',
					'hint'        => trans('admin.interval_hint'),
					'wrapper'     => [
						'class' => 'col-md-6',
					],
				]);
				
				$this->xPanel->addField([
					'name'       => 'listings_limit',
					'label'      => trans('admin.subs_listings_limit_label'),
					'type'       => 'number',
					'attributes' => [
						'placeholder' => trans('admin.subs_listings_limit_label'),
						'min'         => 0,
						'step'        => 1,
					],
					'default'    => config('settings.listing_form.listings_limit', 5),
					'hint'       => trans('admin.subs_listings_limit_hint'),
					'wrapper'    => [
						'class' => 'col-md-6',
					],
				]);
			}
			
			$this->xPanel->addField([
				'name'       => 'pictures_limit',
				'label'      => trans('admin.pictures_limit_label'),
				'type'       => 'number',
				'attributes' => [
					'placeholder' => trans('admin.pictures_limit_label'),
					'min'         => 0,
					'step'        => 1,
				],
				'default'    => config('settings.listing_form.pictures_limit', 5),
				'hint'       => ($this->isSubsPackage)
					? trans('admin.subs_pictures_limit_hint')
					: trans('admin.package_pictures_limit_hint'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'expiration_time',
				'label'      => trans('admin.expiration_time_label'),
				'type'       => 'number',
				'attributes' => [
					'placeholder' => trans('admin.expiration_time_in_days'),
					'min'         => 0,
					'step'        => 1,
				],
				'default'    => config('settings.cron.activated_listings_expiration', 30),
				'hint'       => trans('admin.expiration_time_hint'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
				'newline'    => true,
			]);
			
			if ($this->isPromoPackage) {
				$this->xPanel->addField([
					'name'       => 'facebook_ads_duration',
					'label'      => trans('admin.facebook_ads_duration'),
					'type'       => 'number',
					'attributes' => [
						'min'  => 0,
						'step' => 1,
					],
					'hint'       => trans('admin.external_sponsored_listings_hint', ['provider' => 'Facebook']),
					'wrapper'    => [
						'class' => 'col-md-3',
					],
				]);
				
				$this->xPanel->addField([
					'name'       => 'google_ads_duration',
					'label'      => trans('admin.google_ads_duration'),
					'type'       => 'number',
					'attributes' => [
						'min'  => 0,
						'step' => 1,
					],
					'hint'       => trans('admin.external_sponsored_listings_hint', ['provider' => 'Google']),
					'wrapper'    => [
						'class' => 'col-md-3',
					],
				]);
				
				$this->xPanel->addField([
					'name'       => 'twitter_ads_duration',
					'label'      => trans('admin.twitter_ads_duration'),
					'type'       => 'number',
					'attributes' => [
						'min'  => 0,
						'step' => 1,
					],
					'hint'       => trans('admin.external_sponsored_listings_hint', ['provider' => 'Twitter']),
					'wrapper'    => [
						'class' => 'col-md-3',
					],
				]);
				
				$this->xPanel->addField([
					'name'       => 'linkedin_ads_duration',
					'label'      => trans('admin.linkedin_ads_duration'),
					'type'       => 'number',
					'attributes' => [
						'min'  => 0,
						'step' => 1,
					],
					'hint'       => trans('admin.external_sponsored_listings_hint', ['provider' => 'LinkedIn']),
					'wrapper'    => [
						'class' => 'col-md-3',
					],
					'newline'    => true,
				]);
			}
			
			$this->xPanel->addField([
				'name'       => 'description',
				'label'      => trans('admin.Description'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Description'),
					'rows'        => 6,
				],
				'hint'       => trans('admin.package_description_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'lft',
				'label'      => trans('admin.Position'),
				'type'       => 'number',
				'attributes' => [
					'min'  => 0,
					'step' => 1,
				],
				'hint'       => trans('admin.Quick Reorder') . ': '
					. trans('admin.Enter a position number') . ' '
					. trans('admin.position_number_note'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
				'newline'    => true,
			]);
			
			$this->xPanel->addField([
				'name'    => 'recommended',
				'label'   => trans('admin.recommended'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('admin.recommended_hint'),
				'wrapper' => [
					'class' => 'col-md-6',
				],
			]);
			
			$defaultActiveValue = $this->onCreatePage ? '1' : '0';
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('admin.Active'),
				'type'    => 'checkbox_switch',
				'default' => $defaultActiveValue,
				'hint'    => '<br><br>',
				'wrapper' => [
					'class' => 'col-md-6',
				],
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud($request);
	}
	
	/**
	 * @return array
	 */
	private function getIntervalOptions(): array
	{
		return Package::getEnumValuesAsAssocArray('interval');
	}
}
