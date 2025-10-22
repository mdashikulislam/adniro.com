<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

trait Errors
{
	protected bool $groupedErrors = true;
	protected bool $inlineErrors = false;
	
	public function setErrorDefaults()
	{
		$this->groupedErrors = config('larapen.admin.show_grouped_errors', true);
		$this->inlineErrors = config('larapen.admin.show_inline_errors', false);
	}
	
	// Getters
	
	/**
	 * @return bool
	 */
	public function groupedErrorsEnabled()
	{
		return $this->groupedErrors;
	}
	
	/**
	 * @return bool
	 */
	public function inlineErrorsEnabled()
	{
		return $this->inlineErrors;
	}
	
	// Setters
	
	public function enableGroupedErrors()
	{
		$this->groupedErrors = true;
		
		return $this->groupedErrors;
	}
	
	public function disableGroupedErrors()
	{
		$this->groupedErrors = false;
		
		return $this->groupedErrors;
	}
	
	public function enableInlineErrors()
	{
		$this->inlineErrors = true;
		
		return $this->inlineErrors;
	}
	
	public function disableInlineErrors()
	{
		$this->inlineErrors = false;
		
		return $this->inlineErrors;
	}
}
