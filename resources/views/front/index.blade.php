@extends('front.layouts.master')
@section('search')
	@parent
@endsection
@section('content')
	<div class="main-container sectionable" id="homepage">
		@php
			$isRecentlyViewedShown = false;
		@endphp
		@if (!empty($sections))
			@foreach($sections as $section)
				@php
					$section ??= [];
					$sectionView = data_get($section, 'view');
					$sectionData = (array)data_get($section, 'data');
				@endphp
				{{-- Recently viewed listings (one row), just before the latest listings --}}
				@if (!$isRecentlyViewedShown && str_ends_with((string)$sectionView, 'latest-listings'))
					@include('front.sections.home.recently-viewed')
					@php
						$isRecentlyViewedShown = true;
					@endphp
				@endif
				@if (!empty($sectionView) && view()->exists($sectionView))
					@include($sectionView, [
						'sectionData'  => $sectionData,
						'firstSection' => $loop->first
					])
				@endif
			@endforeach
		@endif
		@if (!$isRecentlyViewedShown)
			@include('front.sections.home.recently-viewed')
		@endif
		
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@section('after_scripts')
	@parent
	<script>
		onDocumentReady((event) => {
			{{--
				Common Issue: Animation not working after loading page, but works when I inspect the page.
				Solution: use startEvent: 'load' in AOS.init({}), it will add event listener on window instead of document.
			--}}
			const options = {
				startEvent: 'load',
			};
			AOS.init(options);
		});
	</script>
@endsection
