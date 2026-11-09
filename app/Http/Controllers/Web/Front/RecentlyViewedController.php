<?php

namespace App\Http\Controllers\Web\Front;

use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * Homepage: "Recently viewed" listings row.
 *
 * The visitor's browser remembers the listings it opened (localStorage, see the listing page)
 * & asks for their cards here (AJAX). Nothing is stored on the server, so the row also works
 * on the pages served from the guests' page cache.
 */
class RecentlyViewedController extends FrontController
{
	public const MAX_ITEMS = 12;
	
	/**
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function __invoke(Request $request): JsonResponse
	{
		// Listings IDs, the most recently viewed first
		$ids = collect(explode(',', (string)$request->query('ids')))
			->map(fn ($id) => trim($id))
			->filter(fn ($id) => ctype_digit($id) && (int)$id > 0)
			->map(fn ($id) => (int)$id)
			->unique()
			->take(self::MAX_ITEMS)
			->values();
		
		if ($ids->isEmpty()) {
			return $this->response('', []);
		}
		
		$postsTable = (new Post())->getTable();
		
		// Live listings only (verified & reviewed by the model's global scopes), from any country:
		// a visitor may have viewed listings of several countries
		$posts = Post::query()
			->unarchived()
			->whereNull($postsTable . '.deleted_at')
			->whereIn($postsTable . '.id', $ids->all())
			->has('city')
			->has('category')
			->with(['category', 'category.parent', 'city', 'picture', 'pictures', 'currency', 'payment', 'payment.package', 'savedByLoggedUser'])
			->get();
		
		// Keep the order of the visitor's history
		$positions = $ids->flip();
		$posts = $posts->sortBy(fn ($post) => $positions[$post->id] ?? PHP_INT_MAX)->values();
		
		if ($posts->isEmpty()) {
			return $this->response('', []);
		}
		
		$params = ['embed' => ['category', 'parent', 'city', 'picture', 'pictures', 'currency', 'payment', 'package', 'savedByLoggedUser', 'postType']];
		$postsCollection = new EntityCollection(PostResource::class, $posts, $params);
		$data = (array)data_get($postsCollection->toResponse($request)->getData(true), 'data', []);
		
		$html = view('front.sections.home.partials.recently-viewed-items', [
			'posts'      => $data,
			'totalPosts' => count($data),
		])->render();
		
		return $this->response($html, array_map(fn ($post) => (int)data_get($post, 'id'), $data));
	}
	
	/**
	 * @param string $html
	 * @param array $ids The listings that are still online (the browser forgets the other ones)
	 * @return \Illuminate\Http\JsonResponse
	 */
	private function response(string $html, array $ids): JsonResponse
	{
		return response()
			->json(['html' => $html, 'ids' => $ids, 'count' => count($ids)])
			->header('Cache-Control', 'no-store, private');
	}
}
