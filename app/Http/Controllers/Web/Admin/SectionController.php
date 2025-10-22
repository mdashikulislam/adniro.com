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

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Controllers\Web\Admin\Traits\SettingsTrait;
use App\Http\Requests\Admin\SectionRequest as StoreRequest;
use App\Http\Requests\Admin\SectionRequest as UpdateRequest;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Throwable;

class SectionController extends PanelController
{
	use SettingsTrait;
	
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Section::class);
		$this->xPanel->setRoute(urlGen()->adminUri('homepage/sections'));
		$this->xPanel->setEntityNameStrings(trans('admin.homepage section'), trans('admin.homepage sections'));
		$this->xPanel->denyAccess(['create', 'delete']);
		$this->xPanel->allowAccess(['reorder']);
		$this->xPanel->enableReorder('label', 1);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'reset_homepage_reorder', 'resetHomepageReOrderTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'reset_homepage_settings', 'resetHomepageSettingsTopButton', 'end');
		$this->xPanel->removeButton('update');
		$this->xPanel->addButtonFromModelFunction('line', 'configure', 'configureInLineButton', 'beginning');
		
		/*
		|--------------------------------------------------------------------------
		| ADVANCED CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel = $this->setupFromCurrentSection($this->xPanel);
		
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
					'name'  => 'label',
					'type'  => 'text',
					'label' => mb_ucfirst(trans('admin.label')),
				],
				filterLogic: fn ($value) => $this->xPanel->addClause('where', 'label', 'LIKE', "%$value%")
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
				'name'          => 'label',
				'label'         => trans('admin.Section'),
				'type'          => 'model_function',
				'function_name' => 'crudLabelColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'description',
				'label' => "",
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('admin.Active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
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
	 * @param \App\Http\Requests\Admin\SectionRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	/**
	 * @param \App\Http\Requests\Admin\SectionRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(UpdateRequest $request)
	{
		$currentResourceId = request()->route()->parameter('section');
		$section = Section::find($currentResourceId);
		
		if (!empty($section)) {
			// Get the right Setting class
			$belongsTo = $section->belongs_to ?? '';
			$name = $section->name ?? '';
			
			// Get class name
			$belongsTo = !empty($belongsTo) ? str($belongsTo)->camel()->ucfirst()->finish('\\')->toString() : '';
			$className = str($name)->camel()->ucfirst()->append('Section');
			
			// Get the class's full qualified name (i.e. with namespace)
			// Ensure the sections are stored in a directory with same name than the section model
			// then use the sections model FQN to find the current section Full Qualified Name (FQN)
			$sectionModelFQN = Section::class; // 'App\Models\Section';
			$namespaceWithSlashes = str($sectionModelFQN)->wrap('\\', '\\')->toString();
			$namespaceWithSlashes = $namespaceWithSlashes . $belongsTo;
			$class = $className->prepend($namespaceWithSlashes)->toString();
			
			// If the class doesn't exist in the core app, try to get it from add-ons
			if (!class_exists($class)) {
				$addOnsSectionNamespacePart = lcfirst($sectionModelFQN); // 'app\Models\Section';
				$namespacePartWithSlashes = str($addOnsSectionNamespacePart)->wrap('\\', '\\')->toString();
				$namespace = plugin_namespace($name) . $namespacePartWithSlashes;
				$namespace = $namespace . $belongsTo;
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
	 * Find a section's real URL
	 * urlGen()->adminUrl('sections/find/{name}')
	 *
	 * @param $name
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function find($name): RedirectResponse
	{
		$section = Section::where('name', $name)->first();
		
		if (empty($section)) {
			$message = trans('admin.section_not_found', ['section' => $name]);
			notification($message, 'error');
			
			return redirect()->back();
		}
		
		$url = urlGen()->adminUrl("sections/{$section->id}/edit");
		
		return redirect()->to($url);
	}
	
	/**
	 * Homepage Sections Actions (Reset Order & Settings)
	 * urlGen()->adminUrl('sections/reset/all/{action}')
	 *
	 * @param $action
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resetAll($action): RedirectResponse
	{
		// Reset the homepage sections reorder
		if ($action == 'reorder') {
			$data = [
				'search_form'      => ['lft' => 0, 'rgt' => 1, 'active' => 1],
				'locations'        => ['lft' => 2, 'rgt' => 3, 'active' => 1],
				'premium_listings' => ['lft' => 4, 'rgt' => 5, 'active' => 1],
				'categories'       => ['lft' => 6, 'rgt' => 7, 'active' => 1],
				'latest_listings'  => ['lft' => 8, 'rgt' => 9, 'active' => 1],
				'stats'            => ['lft' => 10, 'rgt' => 11, 'active' => 1],
				'text_area'        => ['lft' => 12, 'rgt' => 13, 'active' => 0],
				'top_ad'           => ['lft' => 14, 'rgt' => 15, 'active' => 0],
				'bottom_ad'        => ['lft' => 16, 'rgt' => 17, 'active' => 0],
			];
			
			foreach ($data as $name => $value) {
				Section::where('name', $name)->update($value);
			}
			
			$message = trans('admin.sections_reorder_reset_successfully');
			notification($message, 'success');
		}
		
		// Reset all the homepage settings
		if ($action == 'options') {
			$activated = ['field_values' => null, 'active' => 1];
			$unactivated = ['field_values' => null, 'active' => 0];
			
			$data = [
				'search_form'      => $activated,
				'locations'        => $activated,
				'premium_listings' => $activated,
				'categories'       => $activated,
				'latest_listings'  => $activated,
				'stats'            => $activated,
				'text_area'        => $unactivated,
				'top_ad'           => $unactivated,
				'bottom_ad'        => $unactivated,
			];
			
			foreach ($data as $name => $value) {
				Section::where('name', $name)->update($value);
			}
			
			// Delete files which has 'header-' as prefix
			try {
				
				// Get all files in the "app/logo/" path,
				// Filter the ones that match the "*section-*.*" and "*thumb-*-section-*.*" patterns,
				// And delete them.
				$allFiles = $this->disk->files('app/logo/');
				
				$thumbHeaderFiles = preg_grep('/.+\/thumb-.+-section-.+\./', $allFiles);
				$headerFiles = preg_grep('/.+\/section-.+\./', $allFiles);
				$matchingFiles = array_merge($thumbHeaderFiles, $headerFiles);
				
				$this->disk->delete($matchingFiles);
				
			} catch (Throwable $e) {
			}
			
			$message = trans('admin.sections_value_reset_successfully');
			notification($message, 'success');
		}
		
		if (in_array($action, ['reorder', 'options'])) {
			cache()->flush();
		} else {
			$message = trans('admin.no_action_performed');
			notification($message, 'warning');
		}
		
		return redirect()->back();
	}
	
	// PRIVATE
	
	/**
	 * Apply additional setups from the current section
	 *
	 * @param \App\Http\Controllers\Web\Admin\Panel\Library\Panel $xPanel
	 * @return \App\Http\Controllers\Web\Admin\Panel\Library\Panel
	 */
	private function setupFromCurrentSection(Panel $xPanel)
	{
		$currentResourceId = request()->route()->parameter('section');
		$section = Section::find($currentResourceId);
		
		if (!empty($section)) {
			// Get the right Section class
			$belongsTo = $section->belongs_to ?? '';
			$name = $section->name ?? '';
			
			// Get class name
			$belongsTo = !empty($belongsTo) ? str($belongsTo)->camel()->ucfirst()->finish('\\')->toString() : '';
			$className = str($name)->camel()->ucfirst()->append('Section');
			
			// Get the class's full qualified name (i.e. with namespace)
			// Ensure the sections are stored in a directory with same name than the section model
			// then use the sections model FQN to find the current section Full Qualified Name (FQN)
			$sectionModelFQN = Section::class; // 'App\Models\Section';
			$namespaceWithSlashes = str($sectionModelFQN)->wrap('\\', '\\')->toString();
			$namespaceWithSlashes = $namespaceWithSlashes . $belongsTo;
			$class = $className->prepend($namespaceWithSlashes)->toString();
			
			// If the class doesn't exist in the core app, try to get it from add-ons
			if (!class_exists($class)) {
				$addOnsSectionNamespacePart = lcfirst($sectionModelFQN); // 'app\Models\Section';
				$namespacePartWithSlashes = str($addOnsSectionNamespacePart)->wrap('\\', '\\')->toString();
				$namespace = plugin_namespace($name) . $namespacePartWithSlashes;
				$namespace = $namespace . $belongsTo;
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
