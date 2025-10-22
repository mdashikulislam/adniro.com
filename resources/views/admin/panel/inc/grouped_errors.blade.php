{{-- Show the errors, if any --}}
@if ($xPanel->groupedErrorsEnabled())
	@if ($errors->any())
		<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
			<h4 class="alert-heading">{{ trans('admin.please_fix') }}</h4>
			<ul class="mb-0">
				@foreach($errors->all() as $error)
					<li>{!! $error !!}</li>
				@endforeach
			</ul>
		</div>
	@endif
@endif
