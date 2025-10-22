<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use Illuminate\Database\Eloquent\Builder;

trait Query
{
	// ----------------
	// ADVANCED QUERIES
	// ----------------
	
	/**
	 * Add another clause to the query (for ex, a WHERE clause).
	 *
	 * Examples:
	 * // $this->xPanel->addClause('active');
	 * $this->xPanel->addClause('type', 'car');
	 * $this->xPanel->addClause('where', 'name', '==', 'car');
	 * $this->xPanel->addClause('whereName', 'car');
	 * $this->xPanel->addClause('whereHas', 'posts', function($query) {
	 *     $query->activePosts();
	 * });
	 *
	 *
	 * @param $function
	 * @return mixed
	 */
	public function addClause($function)
	{
		return call_user_func_array([$this->query, $function], array_slice(func_get_args(), 1, 3));
	}
	
	/**
	 * Use eager loading to reduce the number of queries on the table view.
	 *
	 * @param $entities
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function with($entities): Builder
	{
		return $this->query->with($entities);
	}
	
	/**
	 * Order the results of the query in a certain way.
	 *
	 * @param $field
	 * @param string $order
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function orderBy($field, string $order = 'asc'): Builder
	{
		return $this->query->orderBy($field, $order);
	}
	
	/**
	 * Order the results of the query by desc.
	 *
	 * @param $field
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function orderByDesc($field): Builder
	{
		return $this->query->orderByDesc($field);
	}
	
	/**
	 * Group the results of the query in a certain way.
	 *
	 * @param $field
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function groupBy($field): Builder
	{
		return $this->query->groupBy($field);
	}
	
	/**
	 * Limit the number of results in the query.
	 *
	 * @param $number
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function limit($number): Builder
	{
		return $this->query->limit($number);
	}
	
	/**
	 * Take a certain number of results from the query.
	 *
	 * @param $number
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function take($number): Builder
	{
		return $this->query->take($number);
	}
	
	/**
	 * Start the result set from a certain number.
	 *
	 * @param $number
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function skip($number): Builder
	{
		return $this->query->skip($number);
	}
	
	/**
	 * Count the number of results.
	 *
	 * @return int
	 */
	public function count(): int
	{
		return $this->query->count();
	}
	
	/**
	 * @return mixed
	 */
	public function withoutAppends()
	{
		return $this->query->withoutAppends();
	}
	
	/**
	 * @param array $appends
	 * @return mixed
	 */
	public function withAppends(array $appends)
	{
		return $this->query->withAppends($appends);
	}
}
