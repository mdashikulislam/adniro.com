@php
	$authUser = auth()->check() ? auth()->user() : null;
	$authUserId = !empty($authUser) ? $authUser->getAuthIdentifier() : 0;
	
	$post ??= [];
	$isReviewsInstalled = (bool)config('plugins.reviews.installed');
	$isOwner = (!empty($authUserId) && $authUserId == data_get($post, 'user_id'));
	$postId = data_get($post, 'id');
	$savedByLoggedUser = (bool)data_get($post, 'p_saved_by_logged_user');
	$isVerified = isVerifiedPost($post);
@endphp
<div class="items-details listing-details">
	
	@if ($isReviewsInstalled)
		{{-- Tab navs (only when reviews are available) --}}
		<ul class="nav listing-tabs" id="itemsDetailsTabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active"
						id="item-details-tab"
						data-bs-toggle="tab"
						data-bs-target="#item-details"
						type="button"
						role="tab"
						aria-controls="item-details"
						aria-selected="true"
				>
					<i class="bi bi-card-text"></i> {{ t('listing_details') }}
				</button>
			</li>
			@php
				$reviewLabel = config('plugins.reviews.name');
			@endphp
			<li class="nav-item" role="presentation">
				<button class="nav-link"
						id="item-{{ $reviewLabel }}-tab"
						data-bs-toggle="tab"
						data-bs-target="#item-{{ $reviewLabel }}"
						type="button"
						role="tab"
						aria-controls="item-{{ $reviewLabel }}"
						aria-selected="false"
				>
					<i class="bi bi-star"></i> {{ trans('reviews::messages.Reviews') }}
					<span class="listing-tab-count">{{ data_get($post, 'rating_count', 0) }}</span>
				</button>
			</li>
		</ul>
	@endif
	
	{{-- Tab panes --}}
	<div class="tab-content listing-panel" id="itemsDetailsTabsContent">
		<div class="tab-pane show active" id="item-details" role="tabpanel" aria-labelledby="item-details-tab" tabindex="0">
			<div class="items-details-info text-wrap from-wysiwyg">
				
				{{-- Description --}}
				<section class="listing-section">
					<h2 class="listing-section-title">
						<span class="listing-section-icon"><i class="bi bi-card-text"></i></span>
						{{ t('Description') }}
					</h2>
					<div class="listing-description detail-line-content">
						{!! data_get($post, 'description') !!}
					</div>
				</section>
				
				{{-- Custom Fields --}}
				@include('front.post.show.partials.details.fields-values')
				
				{{-- Tags --}}
				@if (!empty(data_get($post, 'tags')))
					<section class="listing-section">
						<h2 class="listing-section-title">
							<span class="listing-section-icon"><i class="bi bi-tags"></i></span>
							{{ t('Tags') }}
						</h2>
						<div class="listing-tags">
							@foreach(data_get($post, 'tags') as $iTag)
								<a href="{{ urlGen()->tag($iTag) }}" class="listing-tag">
									<i class="bi bi-hash"></i>{{ $iTag }}
								</a>
							@endforeach
						</div>
					</section>
				@endif
				
				{{-- Actions --}}
				<div class="listing-actions">
					@if ($isOwner)
						<a href="{{ urlGen()->editPost($post) }}" class="listing-action-btn">
							<i class="fa-regular fa-pen-to-square"></i> {{ t('Edit') }}
						</a>
					@else
						@php
							$emailBtn = genEmailContactBtn($post, false, true);
						@endphp
						@if (!empty($emailBtn) && str_contains($emailBtn, '<a '))
							@php
								$msgLink = str_contains($emailBtn, '#quickLogin') ? '#quickLogin' : '#contactUser';
							@endphp
							<a href="{{ $msgLink }}" data-bs-toggle="modal" class="listing-action-btn">
								<i class="bi bi-chat-dots"></i> {{ t('Send a message') }}
							</a>
						@endif
					@endif
					@if ($isVerified && !$isOwner)
						<a class="make-favorite listing-action-btn{{ $savedByLoggedUser ? ' is-saved' : '' }}" id="{{ $postId }}" href="javascript:void(0)">
							@if ($savedByLoggedUser)
								<i class="bi bi-heart-fill"></i> {{ t('Remove favorite') }}
							@else
								<i class="bi bi-heart"></i> {{ t('Save listing') }}
							@endif
						</a>
						<a href="{{ urlGen()->reportPost($post) }}" class="listing-action-btn listing-action-btn-muted">
							<i class="bi bi-flag"></i> {{ t('Report abuse') }}
						</a>
					@endif
				</div>
			</div>
		</div>
		
		@if ($isReviewsInstalled)
			@if (view()->exists('reviews::comments'))
				@include('reviews::comments')
			@endif
		@endif
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		onDocumentReady((event) => {
			/*...*/
		});
	</script>
@endsection
