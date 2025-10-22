{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
--}}
@extends('front.layouts.master')

@php
	$page ??= [];
	$hasHeroImage = !empty(data_get($page, 'image_path'));
@endphp

@section('search')
	@parent
    @include('front.pages.cms.intro')
@endsection

@section('content')
	@include('front.common.spacer')
	<div class="container cms-page">
		@if (!$hasHeroImage)
			@include('helpers.titles.title-4', [
				'title'      => data_get($page, 'name'),
				'titleStyle' => 'color: ' . data_get($page, 'name_color'),
			])
		@endif
		
		<div class="row">
			<div class="col-12">
				<div class="container bg-body-tertiary rounded py-3 px-3">
					<div class="row">
						<div class="col-12">
							@if (!$hasHeroImage)
								@if (data_get($page, 'name') != data_get($page, 'title'))
									<h3 class="text-center fs-3 mb-4" style="color: {!! data_get($page, 'title_color') !!};">
										{{ data_get($page, 'title') }}
									</h3>
								@endif
							@endif
							
							<div class="text-start from-wysiwyg">
								{!! data_get($page, 'content') !!}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		@include('front.layouts.partials.social.horizontal')
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
