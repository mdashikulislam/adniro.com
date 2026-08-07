<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Http\Controllers\Web\Front\Blog;

use App\Http\Controllers\Web\Front\FrontController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class BlogController extends FrontController
{
	/**
	 * Number of posts per page
	 *
	 * @var int
	 */
	protected int $perPage = 12;

	/**
	 * Blog homepage (all the posts)
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index(): View
	{
		$keyword = castToStringOrNull(request()->query('q'));

		$posts = $this->getPostsQuery()
			->when(!empty($keyword), function (Builder $query) use ($keyword) {
				$query->where(function (Builder $query) use ($keyword) {
					$query->where('title', 'LIKE', "%$keyword%")
						->orWhere('excerpt', 'LIKE', "%$keyword%")
						->orWhere('content', 'LIKE', "%$keyword%");
				});
			})
			->paginate($this->perPage)
			->withQueryString();

		$featuredPosts = empty($keyword) && $posts->currentPage() == 1
			? $this->getFeaturedPosts()
			: collect();

		$title = t('blog_page_title');
		$subTitle = t('blog_page_sub_title');
		if (!empty($keyword)) {
			$subTitle = t('blog_search_results_for', ['keyword' => $keyword]);
		}

		$breadcrumbs = [
			['name' => t('blog'), 'url' => urlGen()->blog()],
		];

		$this->setSeo(
			title: $title,
			description: t('blog_page_description', ['app_name' => config('app.name')]),
			keywords: null
		);

		// Keep the internal search results pages out of the search engines index
		$noIndexBlogSearchPages = !empty($keyword);

		return view('front.blog.index', array_merge(
			compact('posts', 'featuredPosts', 'title', 'subTitle', 'keyword', 'breadcrumbs', 'noIndexBlogSearchPages'),
			$this->getSidebarData()
		));
	}

	/**
	 * Blog category archive
	 *
	 * @param string $slug
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function category(string $slug): View|RedirectResponse
	{
		$category = BlogCategory::query()->where('slug', $slug)->first();
		if (empty($category)) {
			abort(404, t('blog_category_not_found'));
		}

		$posts = $this->getPostsQuery()
			->where('category_id', $category->getKey())
			->paginate($this->perPage)
			->withQueryString();

		$title = $category->name;
		$subTitle = strip_tags((string)$category->description);

		$breadcrumbs = [
			['name' => t('blog'), 'url' => urlGen()->blog()],
			['name' => $category->name, 'url' => $category->url],
		];

		$this->setSeo(
			title: $category->seo_title,
			description: $category->seo_description,
			keywords: $category->seo_keywords,
			imageUrl: $category->image_url
		);

		return view('front.blog.index', array_merge(
			compact('posts', 'category', 'title', 'subTitle', 'breadcrumbs'),
			['featuredPosts' => collect(), 'keyword' => null],
			$this->getSidebarData()
		));
	}

	/**
	 * Blog post details
	 *
	 * @param string $slug
	 * @return \Illuminate\Contracts\View\View
	 */
	public function show(string $slug): View
	{
		$post = BlogPost::query()
			->published()
			->availableInCountry()
			->with(['category', 'author'])
			->where('slug', $slug)
			->first();
		if (empty($post)) {
			abort(404, t('blog_post_not_found'));
		}

		// Increment the post's views counter (without touching the entry's timestamps)
		try {
			DB::table('blog_posts')->where('id', $post->getKey())->increment('views');
		} catch (Throwable $e) {
		}

		// Get the related posts (same category)
		$relatedPosts = collect();
		if (!empty($post->category_id)) {
			$relatedPosts = $this->getPostsQuery()
				->where('category_id', $post->category_id)
				->where('id', '!=', $post->getKey())
				->take(3)
				->get();
		}

		$breadcrumbs = [
			['name' => t('blog'), 'url' => urlGen()->blog()],
		];
		if (!empty($post->category)) {
			$breadcrumbs[] = ['name' => $post->category->name, 'url' => $post->category->url];
		}
		$breadcrumbs[] = ['name' => $post->title, 'url' => $post->url];

		$this->setSeo(
			title: $post->seo_title,
			description: $post->seo_description,
			keywords: $post->seo_keywords,
			imageUrl: $post->image_url,
			ogType: 'article'
		);

		/*
		 * Note: The entry is shared as 'blogPost' (and not as 'post'),
		 * since the front master layout applies listings specific logic
		 * (e.g. Open Graph tags rendering) when a 'post' variable is available.
		 */
		$blogPost = $post;

		return view('front.blog.show', array_merge(
			compact('blogPost', 'relatedPosts', 'breadcrumbs'),
			$this->getSidebarData()
		));
	}

	// PRIVATE

	/**
	 * Base query of the published blog posts
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function getPostsQuery(): Builder
	{
		return BlogPost::query()
			->published()
			->availableInCountry()
			->with(['category'])
			->orderByDesc('published_at')
			->orderByDesc('id');
	}

	/**
	 * Get the featured posts
	 *
	 * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
	 */
	private function getFeaturedPosts()
	{
		$cacheParams = [
			'action'  => 'get.blog.featured.posts',
			'country' => config('country.code'),
			'limit'   => 3,
		];

		return caching()->remember(BlogPost::class, $cacheParams, function () {
			return BlogPost::query()
				->published()
				->availableInCountry()
				->featured()
				->with(['category'])
				->orderByDesc('published_at')
				->take(3)
				->get();
		});
	}

	/**
	 * Get the blog sidebar data (shared by all the blog pages)
	 *
	 * @return array
	 */
	private function getSidebarData(): array
	{
		$cacheParams = [
			'action'  => 'get.blog.sidebar',
			'country' => config('country.code'),
		];

		$data = caching()->remember(BlogPost::class, $cacheParams, function () {
			// All the activated categories are listed (including the ones without any post)
			$categories = BlogCategory::query()
				->withCount([
					'posts' => fn ($query) => $query->published()->availableInCountry(),
				])
				->orderBy('lft')
				->orderBy('name')
				->get();

			$recentPosts = BlogPost::query()
				->published()
				->availableInCountry()
				->orderByDesc('published_at')
				->take(5)
				->get();

			return [
				'sidebarCategories' => $categories,
				'sidebarPosts'      => $recentPosts,
			];
		});

		return (array)$data;
	}

	/**
	 * Set the page's SEO information (Meta tags & Open Graph)
	 *
	 * @param string|null $title
	 * @param string|null $description
	 * @param string|null $keywords
	 * @param string|null $imageUrl
	 * @return void
	 */
	private function setSeo(
		?string $title = null,
		?string $description = null,
		?string $keywords = null,
		?string $imageUrl = null,
		?string $ogType = null
	): void {
		$title = !empty($title) ? $title . ' - ' . config('app.name') : config('app.name');
		$description = !empty($description) ? strip_tags($description) : null;

		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		MetaTag::set('keywords', $keywords);

		// Open Graph
		try {
			$this->og->title($title)->description($description);
			if (!empty($ogType)) {
				if ($this->og->has('type')) {
					$this->og->forget('type');
				}
				$this->og->type($ogType);
			}
			if (!empty($imageUrl)) {
				if ($this->og->has('image')) {
					$this->og->forget('image')->forget('image:width')->forget('image:height');
				}
				$this->og->image($imageUrl, [
					'width'  => (int)config('settings.social_share.og_image_width', 1200),
					'height' => (int)config('settings.social_share.og_image_height', 630),
				]);
			}
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
	}
}
