<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

trait PanelExtended
{
	/**
	 * Get the actual resource ID, handling nested routes correctly
	 *
	 * @param $defaultIdentifier
	 * @return float|int|string|null
	 */
	public function getResourceIdentifier($defaultIdentifier = null)
	{
		$parameters = request()->route()->parameters();
		if (empty($parameters)) return $defaultIdentifier;
		
		// Get the last parameter, which should be the actual resource ID
		$identifier = end($parameters);
		
		return (is_numeric($identifier) || is_string($identifier))
			? $identifier
			: $defaultIdentifier;
	}
	
	/**
	 * Get the parent resource ID, handling nested routes correctly
	 * Note: Make sure that {parentIdentifier} parameter is used for nested entities
	 *
	 * @param $defaultIdentifier
	 * @return float|int|mixed|string|null
	 */
	public function getParentResourceIdentifier($defaultIdentifier = null)
	{
		$identifier = request()->route()->parameter('parentIdentifier');
		
		// Validate the fallback parent ID
		$defaultIdentifier = ($this->isNestedEnabled && $this->parentKeyColumn == 'parent_id')
			? $defaultIdentifier
			: null;
		
		return (is_numeric($identifier) || is_string($identifier))
			? $identifier
			: $defaultIdentifier;
	}
	
	/**
	 * @return bool
	 */
	public function isFromNestedPage(): bool
	{
		$segment4 = request()->segment(4);
		
		$route = parse_url($this->route, PHP_URL_PATH);
		$routeSegments = explode('/', $route);
		$routeSegment4 = end($routeSegments);
		
		return ($segment4 == $routeSegment4 || str_starts_with($segment4, 'sub'));
	}
	
	/**
	 * Check if a bulk action is allowed
	 *
	 * @return bool
	 */
	public function isBulkActionAllowed(): bool
	{
		return (
			$this->hasButton('bulk_deletion_button')
			|| $this->hasButton('bulk_activation_button')
			|| $this->hasButton('bulk_deactivation_button')
			|| $this->hasButton('bulk_approval_button')
			|| $this->hasButton('bulk_disapproval_button')
		);
	}
}
