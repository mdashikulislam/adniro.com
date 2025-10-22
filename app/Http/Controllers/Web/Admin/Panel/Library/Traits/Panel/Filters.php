<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Filters\Filter;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Filters\FiltersCollection;
use Illuminate\Support\Collection;

trait Filters
{
	public array|Collection $filters = [];
	
	public function filtersEnabled(): bool
	{
		return !is_array($this->filters);
	}
	
	public function filtersDisabled(): bool
	{
		return is_array($this->filters);
	}
	
	public function enableFilters()
	{
		if ($this->filtersDisabled()) {
			$this->filters = new FiltersCollection();
		}
	}
	
	public function disableFilters()
	{
		$this->filters = [];
	}
	
	public function clearFilters()
	{
		$this->filters = new FiltersCollection;
	}
	
	/**
	 * Add a filter to the CRUD table view.
	 *
	 * @param $options [array] Name, type, label, etc.
	 * @param bool $values [array/closure] The HTML for the filter.
	 * @param bool $filterLogic [closure] Query modification (filtering) logic when filter is active.
	 * @param bool $fallbackLogic [closure] Query modification (filtering) logic when filter is not active.
	 */
	public function addFilter(
		$options,
		array|callable|bool $values = false,
		callable|bool $filterLogic = false,
		callable|bool $fallbackLogic = false
	)
	{
		// if a closure was passed as "values"
		if (is_callable($values)) {
			// get its results
			$values = $values();
		}
		
		// enable the filters functionality
		$this->enableFilters();
		
		// check if another filter with the same name exists
		if (!isset($options['name'])) {
			abort(500, 'All your filters need names.');
		}
		if ($this->filters->contains('name', $options['name'])) {
			abort(500, "Sorry, you can't have two filters with the same name.");
		}
		
		// add a new filter to the interface
		$filter = new Filter($options, $values, $filterLogic);
		$this->filters->push($filter);
		
		// if a closure was passed as "filter_logic"
		if ($this->doingListOperation()) {
			if ($this->request->has($options['name'])) {
				if (is_callable($filterLogic)) {
					// apply it
					$filterLogic($this->request->input($options['name']));
				} else {
					$this->addDefaultFilterLogic($filter->name, $filterLogic);
				}
			} else {
				//if the filter is not active, but fallback logic was supplied
				if (is_callable($fallbackLogic)) {
					// apply the fallback logic
					$fallbackLogic();
				}
			}
		}
	}
	
	/**
	 * @param $name
	 * @param $operator
	 * @return void
	 */
	public function addDefaultFilterLogic($name, $operator)
	{
		$input = request()->all();
		
		// if this filter is active (the URL has it as a GET parameter)
		switch ($operator) {
			// if no operator was passed, just use the equals operator
			case false:
				$this->addClause('where', $name, $input[$name]);
				break;
			
			case 'scope':
				$this->addClause($operator);
				break;
			
			// TODO:
			// whereBetween
			// whereNotBetween
			// whereIn
			// whereNotIn
			// whereNull
			// whereNotNull
			// whereDate
			// whereMonth
			// whereDay
			// whereYear
			// whereColumn
			// like
			
			// sql comparison operators
			case '=':
			case '<=>':
			case '<>':
			case '!=':
			case '>':
			case '>=':
			case '<':
			case '<=':
				$this->addClause('where', $name, $operator, $input[$name]);
				break;
			
			default:
				abort(500, 'Unknown filter operator.');
				break;
		}
	}
	
	/**
	 * @return array|\Illuminate\Support\Collection
	 */
	public function filters()
	{
		return $this->filters;
	}
	
	/**
	 * Modify the attributes of a filter.
	 *
	 * @param string $name The filter name.
	 * @param array|null $modifications An array of changes to be made.
	 * @return Filter The filter that has suffered modifications, for daisy-chaining methods.
	 */
	public function modifyFilter(string $name, ?array $modifications = [])
	{
		/** @var Filter $filter */
		$filter = $this->filters->firstWhere('name', $name);
		
		if (empty($filter)) {
			abort(500, 'CRUD Filter "' . $name . '" not found. Please check the filter exists before you modify it.');
		}
		
		if (is_array($modifications)) {
			foreach ($modifications as $key => $value) {
				$filter->{$key} = $value;
			}
		}
		
		return $filter;
	}
	
	/**
	 * @param $name
	 * @return void
	 */
	public function removeFilter($name)
	{
		$this->filters = $this->filters->reject(function ($filter) use ($name) {
			return $filter->name == $name;
		});
	}
	
	/**
	 * @return void
	 */
	public function removeAllFilters()
	{
		$this->filters = collect();
	}
	
	/**
	 * Determine if the current CRUD action is a list operation (using standard or ajax DataTables).
	 *
	 * @return bool
	 */
	public function doingListOperation(): bool
	{
		$requestedUrl = $this->request->url();
		
		$routeUrl = url($this->route);
		$searchRouteUrl = $this->getUrl('search');
		$searchRouteUrl = urlBuilder($searchRouteUrl)->removeAllParameters()->toString();
		
		return match ($requestedUrl) {
			$routeUrl       => !in_array($this->request->getMethod(), ['POST', 'PATCH']),
			$searchRouteUrl => true,
			default         => false,
		};
	}
}
