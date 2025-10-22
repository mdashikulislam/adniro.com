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

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../../Helpers/Common/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

/*
------------------------------------------------------------------------------------
The "field" field value for "settings" table
------------------------------------------------------------------------------------
text            => {"name":"value","label":"Value","type":"text"}
textarea        => {"name":"value","label":"Value","type":"textarea"}
checkbox        => {"name":"value","label":"Activation","type":"checkbox"}
upload (image)  => {"name":"value","label":"Value","type":"image","upload":"true","disk":"uploads","default":"images/logo@2x.png"}
selectbox       => {"name":"value","label":"Value","type":"select_from_array","options":OPTIONS}
                => {"default":"Default","blue":"Blue","yellow":"Yellow","green":"Green","red":"Red"}
                => {"smtp":"SMTP","mailgun":"Mailgun","ses":"Amazon SES","mail":"PHP Mail","sendmail":"Sendmail"}
                => {"sandbox":"sandbox","live":"live"}
------------------------------------------------------------------------------------
*/

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Controllers\Web\Admin\Traits\SettingsTrait;
use App\Http\Requests\Admin\SettingRequest as StoreRequest;
use App\Http\Requests\Admin\SettingRequest as UpdateRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Throwable;

class SettingController extends PanelController
{
	use SettingsTrait;
	
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Setting::class);
		$this->xPanel->addClause('where', 'active', 1);
		$this->xPanel->setEntityNameStrings(trans('admin.general setting'), trans('admin.general settings'));
		$this->xPanel->setRoute(urlGen()->adminUri('settings'));
		$this->xPanel->enableReorder('label', 1);
		$this->xPanel->allowAccess(['reorder']);
		$this->xPanel->denyAccess(['create', 'delete']);
		$this->xPanel->setDefaultPageLength(100);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
			$this->xPanel->orderBy('id');
		}
		
		$this->xPanel->removeButton('update');
		$this->xPanel->addButtonFromModelFunction('line', 'configure', 'configureInLineButton', 'beginning');
		
		/*
		|--------------------------------------------------------------------------
		| ADVANCED CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel = $this->setupFromCurrentSetting($this->xPanel);
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			$this->xPanel->addColumn([
				'name'          => 'label',
				'label'         => 'Setting',
				'type'          => 'model_function',
				'function_name' => 'crudLabelColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'description',
				'label' => '',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		// if ($this->onCreatePage || $this->onEditPage) {}
	}
	
	/**
	 * @param \App\Http\Requests\Admin\SettingRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	/**
	 * @param \App\Http\Requests\Admin\SettingRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(UpdateRequest $request): RedirectResponse
	{
		$currentResourceId = request()->route()->parameter('setting');
		$setting = Setting::find($currentResourceId);
		
		if (!empty($setting)) {
			// Get the right Setting class
			$name = $setting->name ?? '';
			
			// Get class name
			$className = str($name)->camel()->ucfirst()->append('Setting');
			
			// Get the class's full qualified name (i.e. with namespace)
			// Ensure the settings are stored in a directory with same name than the setting model
			// then use the settings model FQN to find the current setting Full Qualified Name (FQN)
			$settingModelFQN = Setting::class; // 'App\Models\Setting';
			$namespaceWithSlashes = str($settingModelFQN)->wrap('\\', '\\')->toString();
			$class = $className->prepend($namespaceWithSlashes)->toString();
			
			// If the class doesn't exist in the core app, try to get it from add-ons
			if (!class_exists($class)) {
				$addOnsSettingNamespacePart = lcfirst($settingModelFQN); // 'app\Models\Setting';
				$namespacePartWithSlashes = str($addOnsSettingNamespacePart)->wrap('\\', '\\')->toString();
				$namespace = plugin_namespace($name) . $namespacePartWithSlashes;
				$class = $className->prepend($namespace)->toString();
			}
			
			if (class_exists($class)) {
				if (method_exists($class, 'passedValidation')) {
					$request = $class::passedValidation($request);
				}
			}
		}
		
		return $this->updateTrait($request);
	}
	
	/**
	 * Find a setting's real URL
	 * urlGen()->adminUrl('settings/find/{name}')
	 *
	 * @param $name
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function find($name): RedirectResponse
	{
		$setting = Setting::where('name', $name)->first();
		
		if (empty($setting)) {
			$message = trans('admin.setting_not_found', ['setting' => $name]);
			notification($message, 'error');
			
			return redirect()->back();
		}
		
		$url = urlGen()->adminUrl("settings/{$setting->id}/edit");
		
		return redirect()->to($url);
	}
	
	/**
	 * Reset a setting by its key
	 * urlGen()->adminUrl('settings/reset/{name}')
	 *
	 * @param $name
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function reset($name): RedirectResponse
	{
		// Allow only the 'pagination' setting (for the moment... waiting the full feature)
		if ($name != 'pagination') {
			$message = trans('admin.setting_reset_not_allowed', ['setting' => $name]);
			notification($message, 'info');
			
			return redirect()->back();
		}
		
		// $name is always 'pagination' here. @todo: Add support for the other setting groups
		try {
			$setting = Setting::where('name', $name)->first();
			
			if (!empty($setting)) {
				$purchaseCode = null;
				if ($name === 'app') {
					$purchaseCode = $setting->field_values['purchase_code'] ?? null;
				}
				
				$setting->field_values = !empty($purchaseCode) ? ['purchase_code' => $purchaseCode] : null;
				$setting->save();
				
				// Clear all the cache
				cache()->flush();
				
				$message = trans('admin.setting_reset_success', ['setting' => $setting->name]);
				notification($message, 'success');
			} else {
				$message = trans('admin.setting_not_found', ['setting' => $name]);
				notification($message, 'warning');
			}
		} catch (Throwable $e) {
			notification($e->getMessage(), 'warning');
		}
		
		return redirect()->back();
	}
	
	// PRIVATE
	
	/**
	 * Apply additional setups from the current setting
	 *
	 * @param \App\Http\Controllers\Web\Admin\Panel\Library\Panel $xPanel
	 * @return \App\Http\Controllers\Web\Admin\Panel\Library\Panel
	 */
	private function setupFromCurrentSetting(Panel $xPanel)
	{
		$currentResourceId = request()->route()->parameter('setting');
		$setting = Setting::find($currentResourceId);
		
		if (!empty($setting)) {
			// Get the right Setting class
			$name = $setting->name ?? '';
			
			// Get class name
			$className = str($name)->camel()->ucfirst()->append('Setting');
			
			// Get the class's full qualified name (i.e. with namespace)
			// Ensure the settings are stored in a directory with same name than the setting model
			// then use the settings model FQN to find the current setting Full Qualified Name (FQN)
			$settingModelFQN = Setting::class; // 'App\Models\Setting';
			$namespaceWithSlashes = str($settingModelFQN)->wrap('\\', '\\')->toString();
			$class = $className->prepend($namespaceWithSlashes)->toString();
			
			// If the class doesn't exist in the core app, try to get it from add-ons
			if (!class_exists($class)) {
				$addOnsSettingNamespacePart = lcfirst($settingModelFQN); // 'app\Models\Setting';
				$namespacePartWithSlashes = str($addOnsSettingNamespacePart)->wrap('\\', '\\')->toString();
				$namespace = plugin_namespace($name) . $namespacePartWithSlashes;
				$class = $className->prepend($namespace)->toString();
			}
			
			if (class_exists($class)) {
				if (method_exists($class, 'setup')) {
					$xPanel = $class::setup($xPanel);
				}
			}
		}
		
		return $xPanel;
	}
}
