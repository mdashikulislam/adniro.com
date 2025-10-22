@php
	$authUser = auth()->check() ? auth()->user() : null;
	$authUserId = !empty($authUser) ? $authUser->getAuthIdentifier() : 0;
	
	$post ??= [];
@endphp
<div class="items-details">
	<div class="row">
		<div class="col-12">
			{{-- Tab navs --}}
			<ul class="nav nav-tabs" id="itemsDetailsTabs" role="tablist">
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
						<span class="fs-5 fw-bold">{{ t('listing_details') }}</span>
					</button>
				</li>
				@if (config('plugins.reviews.installed'))
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
							<span class="fs-5 fw-bold">
								{{ trans('reviews::messages.Reviews') }} ({{ data_get($post, 'rating_count', 0) }})
							</span>
						</button>
					</li>
				@endif
			</ul>
			
			{{-- Tab panes --}}
			<div class="tab-content border border-top-0 rounded-bottom bg-body p-3 mb-3" id="itemsDetailsTabsContent">
				<div class="tab-pane show active" id="item-details" role="tabpanel" aria-labelledby="item-details-tab" tabindex="0">
					<div class="row pb-3">
						<div class="items-details-info col-md-12 col-sm-12 col-12 text-wrap from-wysiwyg">
							
							<div class="row border-bottom pb-2 mb-3">
								{{-- Location --}}
								<div class="col-md-6 col-sm-6 col-6">
									<h4 class="p-0 fs-5 fw-normal">
										<span class="fw-bold"><i class="bi bi-geo-alt"></i> {{ t('location') }}: </span>
										<span>
											<a href="{!! urlGen()->city(data_get($post, 'city')) !!}" class="{{ linkClass() }}">
												{{ data_get($post, 'city.name') }}
											</a>
										</span>
									</h4>
								</div>
								
								{{-- Price / Salary --}}
								<div class="col-md-6 col-sm-6 col-6 text-end">
									<h4 class="p-0 fs-5 fw-normal">
										<span class="fw-bold">
											{{ data_get($post, 'price_label') }}
										</span>
										<span>
											{!! data_get($post, 'price_formatted') !!}
											@if (data_get($post, 'negotiable') == 1)
												<small class="badge rounded-pill text-bg-info"> {{ t('negotiable') }}</small>
											@endif
										</span>
									</h4>
								</div>
							</div>
							
							{{-- Description --}}
							<div class="row">
								<div class="col-12 detail-line-content">
									{!! data_get($post, 'description') !!}
								</div>
							</div>
							
							{{-- Custom Fields --}}
							@include('front.post.show.partials.details.fields-values')
							
							{{-- Tags --}}
							@if (!empty(data_get($post, 'tags')))
								<div class="row mt-3">
									<div class="col-12">
										<h4 class="p-0 my-3 fs-5"><i class="bi bi-tags"></i> {{ t('Tags') }}:</h4>
										@foreach(data_get($post, 'tags') as $iTag)
											<span class="d-inline-block border border-inverse bg-body-tertiary rounded-1 py-1 px-2 my-1 me-1">
												<a href="{{ urlGen()->tag($iTag) }}" class="{{ linkClass() }}">
													{{ $iTag }}
												</a>
											</span>
										@endforeach
									</div>
								</div>
							@endif
							
							{{-- Actions --}}
							@if (empty($authUserId) || $authUserId != data_get($post, 'user_id'))
								<div class="row mt-4 fs-1 text-center">
									<div class="col-4">
										@if (!empty($authUser))
											@if ($authUserId == data_get($post, 'user_id'))
												<a href="{{ urlGen()->editPost($post) }}" class="{{ linkClass() }}">
													<i class="fa-regular fa-pen-to-square" data-bs-toggle="tooltip" title="{{ t('Edit') }}"></i>
												</a>
											@else
												{!! genEmailContactBtn($post, false, true) !!}
											@endif
										@else
											{!! genEmailContactBtn($post, false, true) !!}
										@endif
									</div>
									@if (isVerifiedPost($post))
										<div class="col-4">
											@php
												$postId = data_get($post, 'id');
												$savedByLoggedUser = (bool)data_get($post, 'p_saved_by_logged_user');
											@endphp
											<a class="make-favorite {{ linkClass() }}" id="{{ $postId }}" href="javascript:void(0)">
												@if ($savedByLoggedUser)
													<i class="bi bi-heart-fill" data-bs-toggle="tooltip" title="{{ t('Remove favorite') }}"></i>
												@else
													<i class="bi bi-heart" data-bs-toggle="tooltip" title="{{ t('Save listing') }}"></i>
												@endif
											</a>
										</div>
										<div class="col-4">
											<a href="{{ urlGen()->reportPost($post) }}" class="{{ linkClass() }}">
												<i class="fa-regular fa-flag" data-bs-toggle="tooltip" title="{{ t('Report abuse') }}"></i>
											</a>
										</div>
									@endif
								</div>
							@endif
						</div>
					
					</div>
				</div>
				
				@if (config('plugins.reviews.installed'))
					@if (view()->exists('reviews::comments'))
						@include('reviews::comments')
					@endif
				@endif
			</div>
		</div>
		
		<div class="col-12">
			<div class="hstack gap-3 text-start">
				@if (!empty($authUser))
					@if ($authUserId == data_get($post, 'user_id'))
						<a class="btn btn-outline-primary" href="{{ urlGen()->editPost($post) }}">
							<i class="fa-regular fa-pen-to-square"></i> {{ t('Edit') }}
						</a>
					@else
						{!! genPhoneNumberBtn($post) !!}
						{!! genEmailContactBtn($post) !!}
					@endif
				@else
					{!! genPhoneNumberBtn($post) !!}
					{!! genEmailContactBtn($post) !!}
				@endif
			</div>
		</div>
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
