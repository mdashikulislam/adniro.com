<?php

namespace App\Models\Post;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * "Similar listings" picked as the listing's neighbors: the listings posted just before it
 * (then just after it, when there aren't enough older ones) in the same filtered set.
 *
 * Replaces a random order, which sorted the whole set (e.g. a category's listings) on
 * every listing view and linked to different listings on every render. Neighbors are read
 * from an index range, and every listing gets stable links from the listings posted right
 * after it, so old listings stay reachable through internal links, not only the sitemaps.
 */
trait SimilarNeighbors
{
	/**
	 * @param \Illuminate\Database\Eloquent\Builder $posts The filtered query (without ORDER BY)
	 * @param int $limit
	 * @return \Illuminate\Pagination\LengthAwarePaginator
	 */
	protected function takeNeighbors(Builder $posts, int $limit): LengthAwarePaginator
	{
		$limit = max($limit, 1);
		$idColumn = $this->getTable() . '.' . $this->getKeyName();
		$currentId = (int)$this->getKey();
		
		$items = (clone $posts)
			->where($idColumn, '<', $currentId)
			->orderByDesc($idColumn)
			->take($limit)
			->get();
		
		if ($items->count() < $limit) {
			$newerItems = (clone $posts)
				->where($idColumn, '>', $currentId)
				->orderBy($idColumn)
				->take($limit - $items->count())
				->get();
			$items = $items->concat($newerItems)->values();
		}
		
		return new LengthAwarePaginator($items, $items->count(), $limit, 1, [
			'path' => LengthAwarePaginator::resolveCurrentPath(),
		]);
	}
}
