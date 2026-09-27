@php
	use App\Enums\BootstrapColor;
	use Illuminate\Support\Number;

	$posts ??= [];
	$totalPosts ??= 0;

	$city ??= null;
	$cat ??= null;

	$defaultCols = 4;
	$customCols ??= null;
	$lgCols = !empty($customCols)
		? (int)$customCols
		: (int)config('settings.listings_list.grid_view_cols', $defaultCols);
	$lgCols = Number::clamp($lgCols, min: 2, max: 4);
	$mdCols = ($lgCols >= 3) ? 3 : $lgCols;
	$smCols = ($lgCols >= 2) ? 2 : $lgCols;
	
	$showPostInfo = (
		(!config('settings.listings_list.hide_post_type') && config('settings.listing_form.show_listing_type'))
		|| !config('settings.listings_list.hide_date')
		|| !config('settings.listings_list.hide_category')
		|| !config('settings.listings_list.hide_location')
	);
@endphp
@if (!empty($posts) && $totalPosts > 0)
	<div class="row row-cols-lg-{{ $lgCols }} row-cols-md-{{ $mdCols }} row-cols-{{ $smCols }} g-3 py-2 grid-view listing-grid">
		@foreach($posts as $key => $post)
			@php
				$pictureAttr = [
					'class' => 'lazyload listing-card-picture',
				];

				$postUrl = urlGen()->post($post);
				$parentCatUrl = null;
				if (!empty(data_get($post, 'category.parent'))) {
					$parentCatUrl = urlGen()->category(data_get($post, 'category.parent'), null, $city);
				}
				$catUrl = urlGen()->category(data_get($post, 'category'), null, $city);
				$locationUrl = urlGen()->city(data_get($post, 'city'), null, $cat);
				
				$postId = data_get($post, 'id');
				$savedByLoggedUser = (bool)data_get($post, 'p_saved_by_logged_user');
				
				$isFeatured = (data_get($post, 'featured') == 1);
				$package = data_get($post, 'payment.package');
				$ribbon = $isFeatured ? data_get($package, 'ribbon') : null;
				$packageShortName = data_get($package, 'short_name');
				$hasPackageBadge = (!empty($package) && data_get($package, 'has_badge') == 1);
				
				$countPictures = (int)data_get($post, 'count_pictures');
				$priceFormatted = data_get($post, 'price_formatted');
				$isTextPrice = !preg_match('/\d/', strip_tags((string)$priceFormatted));
			@endphp
			<div class="col item-list d-flex">
				<article class="listing-card w-100{{ $isFeatured ? ' listing-card-featured' : '' }}">
					
					{{-- Media --}}
					<div class="listing-card-media">
						<a href="{{ $postUrl }}" class="listing-card-img" aria-label="{{ data_get($post, 'title') }}">
							@php
								$src = data_get($post, 'picture.url.medium');
								$webpSrc = data_get($post, 'picture.url.webp.medium');
								$alt = str(data_get($post, 'title'))->slug();
								echo generateImageHtml($src, $alt, $webpSrc, $pictureAttr);
							@endphp
						</a>
						
						@if (!empty($ribbon) && !empty($packageShortName))
							@php
								$ribbonColorClass = BootstrapColor::Badge->getColorClass($ribbon);
							@endphp
							<span class="badge rounded-pill {{ $ribbonColorClass }} listing-card-ribbon">
								{{ $packageShortName }}
							</span>
						@endif
						
						@if ($countPictures > 0)
							<span class="listing-card-count">
								<i class="fa-solid fa-camera"></i> {{ $countPictures }}
							</span>
						@endif
					</div>
					
					{{-- Body --}}
					<div class="listing-card-body">
						@if ($showPostInfo && !config('settings.listings_list.hide_category'))
							<div class="listing-card-cat">
								@if (!empty(data_get($post, 'category.parent')))
									<a href="{!! $parentCatUrl !!}" class="{{ linkClass() }}">{{ data_get($post, 'category.parent.name') }}</a>
									<i class="bi bi-chevron-right"></i>
								@endif
								<a href="{!! $catUrl !!}" class="{{ linkClass() }}">{{ data_get($post, 'category.name') }}</a>
							</div>
						@endif
						
						<h5 class="listing-card-title">
							<a href="{{ $postUrl }}" class="{{ linkClass('body-emphasis') }}">
								{{ str(data_get($post, 'title'))->limit(70) }}
							</a>
						</h5>
						
						@if ($showPostInfo)
							<ul class="listing-card-meta list-unstyled mb-0">
								@if (
									!config('settings.listings_list.hide_post_type')
									&& config('settings.listing_form.show_listing_type')
									&& !empty(data_get($post, 'postType'))
								)
									<li>
										<span class="badge rounded-pill text-bg-secondary fw-normal"
										      data-bs-toggle="tooltip"
										      data-bs-placement="bottom"
										      title="{{ data_get($post, 'postType.label') }}"
										>
											{{ strtoupper(mb_substr(data_get($post, 'postType.label'), 0, 1)) }}
										</span>
									</li>
								@endif
								@if (!config('settings.listings_list.hide_location') && !empty(data_get($post, 'city.name')))
									<li>
										<i class="bi bi-geo-alt"></i>
										<a href="{!! $locationUrl !!}" class="{{ linkClass('secondary') }}">{{ data_get($post, 'city.name') }}</a>
										{{ data_get($post, 'distance_info') }}
									</li>
								@endif
								@if (!config('settings.listings_list.hide_date'))
									<li>
										<i class="fa-regular fa-clock"></i> {!! data_get($post, 'created_at_formatted') !!}
									</li>
								@endif
							</ul>
						@endif
					</div>
					
					{{-- Footer: Reviews, Price & Favourite Button --}}
					<div class="listing-card-footer">
						@if (config('plugins.reviews.installed'))
							@if (view()->exists('reviews::ratings-list'))
								<div class="listing-card-reviews w-100 mb-2">
									@include('reviews::ratings-list')
								</div>
							@endif
						@endif
						
						<div class="listing-card-price{{ $isTextPrice ? ' listing-card-price-text' : '' }}">
							{!! $priceFormatted !!}
						</div>
						
						<div class="listing-card-actions">
							@if ($hasPackageBadge)
								<span class="listing-card-package">
									<i class="fa-solid fa-certificate"></i> {{ $packageShortName }}
								</span>
							@endif
							@if ($savedByLoggedUser)
								<a class="btn btn-success btn-xs small make-favorite listing-fav" id="{{ $postId }}" title="{{ t('Remove favorite') }}">
									<i class="bi bi-heart-fill small"></i> <span>{{ t('Saved') }}</span>
								</a>
							@else
								<a class="btn btn-outline-secondary btn-xs small make-favorite listing-fav" id="{{ $postId }}" title="{{ t('Save listing') }}">
									<i class="bi bi-heart small"></i> <span>{{ t('Save') }}</span>
								</a>
							@endif
						</div>
					</div>
					
				</article>
			</div>
		@endforeach
	</div>
@else
	<div class="py-5 text-center w-100">
		{{ t('no_result_refine_your_search') }}
	</div>
@endif

@section('after_scripts')
	@parent
	<script>
		{{-- Favorites Translation --}}
		var lang = {
			labelSavePostSave: "{!! t('Save listing') !!}",
			labelSavePostRemove: "{!! t('Remove favorite') !!}",
			loginToSavePost: "{!! t('Please log in to save the Listings') !!}",
			loginToSaveSearch: "{!! t('Please log in to save your search') !!}"
		};
	</script>
@endsection
