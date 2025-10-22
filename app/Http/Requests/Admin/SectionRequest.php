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

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\SectionRequest\SpacingRequest;
use App\Models\Section;

class SectionRequest extends Request
{
	protected array $rulesMessages = [];
	protected array $rulesAttributes = [];
	
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation(): void
	{
		$input = $this->all();
		
		// Get the right Section Request class
		$sectionClass = $this->getSectionClass();
		if (class_exists($sectionClass)) {
			$formRequest = new $sectionClass();
			if (method_exists($formRequest, 'customPrepareForValidation')) {
				$input = $formRequest->customPrepareForValidation($input);
			}
		}
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [];
		
		// Get the right Section Request class
		$sectionClass = $this->getSectionClass();
		if (class_exists($sectionClass)) {
			$formRequest = new $sectionClass();
			$rules = $formRequest->rules();
			$this->rulesMessages = $formRequest->messages();
			$this->rulesAttributes = $formRequest->attributes();
		}
		
		// Default validation (For all section requests)
		$spacingRequest = new SpacingRequest();
		$rules = array_merge($rules, $spacingRequest->rules());
		$this->rulesMessages = array_merge($this->rulesMessages, $spacingRequest->messages());
		$this->rulesAttributes = array_merge($this->rulesAttributes, $spacingRequest->attributes());
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		if (!empty($this->rulesMessages)) {
			$messages = $messages + $this->rulesMessages;
		}
		
		return array_merge(parent::messages(), $messages);
	}
	
	/**
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [];
		
		if (!empty($this->rulesAttributes)) {
			$attributes = $attributes + $this->rulesAttributes;
		}
		
		return array_merge(parent::attributes(), $attributes);
	}
	
	/**
	 * Get the right Section class
	 *
	 * @return string
	 */
	private function getSectionClass(): string
	{
		$section = $this->getSection();
		if (empty($section)) return '';
		
		$name = $section->name ?? '';
		
		// Get class name
		$className = str($name)->camel()->ucfirst()->append('Request');
		
		// Get class full qualified name (i.e. with namespace)
		$namespace = '\App\Http\Requests\Admin\SectionRequest\\';
		$class = $className->prepend($namespace)->toString();
		
		// If the class doesn't exist in the core app, try to get it from add-ons
		if (!class_exists($class)) {
			$namespace = plugin_namespace($name) . '\app\Http\Requests\Admin\SectionRequest\\';
			$class = $className->prepend($namespace)->toString();
		}
		
		return $class;
	}
	
	/**
	 * Get the section
	 */
	private function getSection()
	{
		$section = null;
		
		// Get right model class & its segment index
		$segmentIndex = 3;
		$model = Section::class;
		if (routeActionHas('DomainSectionController')) {
			$segmentIndex = 5;
			$model = '\extras\plugins\domainmapping\app\Models\DomainSection';
		}
		
		if (class_exists($model)) {
			// Get the section's ID
			$sectionId = $this->segment($segmentIndex);
			if (!empty($sectionId)) {
				/**
				 * Get the section
				 *
				 * @var \Illuminate\Database\Eloquent\Model $model
				 */
				$section = $model::find($sectionId);
			}
		}
		
		return $section;
	}
}
