<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Buttons\Button;

trait Buttons
{
	// ------------
	// BUTTONS
	// ------------
	
	// TODO: $this->crud->reorderButtons('stack_name', ['one', 'two']);
	
	/**
	 * Add a button to the CRUD table view.
	 *
	 * @param string $stack Where should the button be visible? Options: top, line, bottom
	 * @param string $name The name of the button. Unique.
	 * @param string $type Type of button: view or model_function
	 * @param string $content The HTML for the button
	 * @param string|bool|null $position Where in the stack it should be placed: beginning or end
	 */
	public function addButton($stack, $name, $type, $content, string|bool|null $position = false, bool $replaceExisting = true): void
	{
		if (!$position) {
			$position = match ($stack) {
				'line'  => 'beginning',
				default => 'end',
			};
		}
		
		if ($replaceExisting) {
			$this->removeButton($name, $stack);
		}
		
		$button = new Button($stack, $name, $type, $content);
		switch ($position) {
			case 'beginning':
				$this->buttons->prepend($button);
				break;
			
			default:
				$this->buttons->push($button);
				break;
		}
	}
	
	/**
	 * @param $stack
	 * @param $name
	 * @param $model_function_name
	 * @param string|bool|null $position
	 * @return void
	 */
	public function addButtonFromModelFunction($stack, $name, $model_function_name, string|bool|null $position = false): void
	{
		$this->addButton($stack, $name, 'model_function', $model_function_name, $position);
	}
	
	/**
	 * @param $stack
	 * @param $name
	 * @param $view
	 * @param string|bool|null $position
	 * @return void
	 */
	public function addButtonFromView($stack, $name, $view, string|bool|null $position = false): void
	{
		$view = "admin.panel.buttons.{$stack}.{$view}";
		
		$this->addButton($stack, $name, 'view', $view, $position);
	}
	
	/**
	 * @return mixed
	 */
	public function buttons()
	{
		return $this->buttons;
	}
	
	/**
	 * @return void
	 */
	public function initButtons(): void
	{
		$this->buttons = collect();
		
		// line stack
		$this->addButton('line', 'preview', 'view', 'admin.panel.buttons.line.preview', 'end');
		$this->addButton('line', 'update', 'view', 'admin.panel.buttons.line.update', 'end');
		$this->addButton('line', 'revisions', 'view', 'admin.panel.buttons.line.revisions', 'end');
		$this->addButton('line', 'delete', 'view', 'admin.panel.buttons.line.delete', 'end');
		
		// top stack
		$this->addButton('top', 'parent', 'view', 'admin.panel.buttons.top.parent');
		$this->addButton('top', 'create', 'view', 'admin.panel.buttons.top.create');
		$this->addButton('top', 'reorder', 'view', 'admin.panel.buttons.top.reorder');
	}
	
	/**
	 * Modify the attributes of a button.
	 *
	 * @param string $name The button name.
	 * @param array|null $modifications The attributes and their new values.
	 * @return Button The button that has suffered the changes, for daisy-chaining methods.
	 */
	public function modifyButton(string $name, array $modifications = null)
	{
		/**
		 * @var Button|null $button
		 */
		$button = $this->buttons()->firstWhere('name', $name);
		
		if (!$button) {
			abort(500, 'CRUD Button "' . $name . '" not found. Please check the button exists before you modify it.');
		}
		
		if (is_array($modifications)) {
			foreach ($modifications as $key => $value) {
				$button->{$key} = $value;
			}
		}
		
		return $button;
	}
	
	/**
	 * Remove a button from the CRUD panel.
	 *
	 * @param string $name  Button name.
	 * @param string|null $stack Optional stack name.
	 */
	public function removeButton(string $name, string $stack = null): void
	{
		$this->buttons = $this->buttons->reject(function ($button) use ($name, $stack) {
			return ($stack == null)
				? $button->name == $name
				: ($button->stack == $stack) && ($button->name == $name);
		});
	}
	
	/**
	 * Remove all buttons
	 *
	 * @return void
	 */
	public function removeAllButtons(): void
	{
		$this->buttons = collect();
	}
	
	/**
	 * @param $stack
	 * @return void
	 */
	public function removeAllButtonsFromStack($stack): void
	{
		$this->buttons = $this->buttons->reject(function ($button) use ($stack) {
			return $button->stack == $stack;
		});
	}
	
	/**
	 * @param $name
	 * @param $stack
	 * @return void
	 */
	public function removeButtonFromStack($name, $stack): void
	{
		$this->buttons = $this->buttons->reject(function ($button) use ($name, $stack) {
			return $button->name == $name && $button->stack == $stack;
		});
	}
	
	/**
	 * @param $name
	 * @param null $stack
	 * @return bool
	 */
	public function hasButton($name, $stack = null): bool
	{
		$buttonFound = $this->buttons->first(function ($button) use ($name, $stack) {
			if (!empty($stack)) {
				return $button->name == $name && $button->stack == $stack;
			} else {
				return $button->name == $name;
			}
		});
		
		return (!empty($buttonFound) && $buttonFound->name == $name);
	}
}
