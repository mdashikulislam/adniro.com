@extends('front.layouts.master')
@section('search')
	@parent
@endsection
@section('content')
	<div class="main-container sectionable" id="homepage">
		@if (!empty($sections))
			@foreach($sections as $section)
				@php
					$section ??= [];
					$sectionView = data_get($section, 'view');
					$sectionData = (array)data_get($section, 'data');
				@endphp
				@if (!empty($sectionView) && view()->exists($sectionView))
					@include($sectionView, [
						'sectionData'  => $sectionData,
						'firstSection' => $loop->first
					])
				@endif
			@endforeach
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
