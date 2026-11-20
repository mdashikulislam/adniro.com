{{-- AI review queue: reject a listing (adniro:ai) --}}
@extends('admin.layouts.master')

@section('header')
	<div class="row page-titles">
		<div class="col-12 align-self-center">
			<h2 class="mb-0">{{ trans('ai.queue_reject_title') }}</h2>
		</div>
	</div>
@endsection

@section('content')
	@php
		$selected = old('categories', (array)($latest?->categories ?? []));
		$reason = old('reason', $latest?->user_reason);
	@endphp
	<div class="row">
		<div class="col-lg-8 col-12">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">{{ $post->title }} <small class="text-muted">#{{ $post->id }}</small></h4>
					<p class="small text-muted">{{ trans('ai.queue_reject_help') }}</p>
					
					@if ($errors->any())
						<div class="alert alert-danger">
							@foreach ($errors->all() as $error)
								<div>{{ $error }}</div>
							@endforeach
						</div>
					@endif
					
					<form method="post" action="{{ urlGen()->adminUrl('moderation/' . $post->id . '/reject') }}">
						@csrf
						<label class="form-label fw-bold">{{ trans('ai.queue_categories') }}</label>
						<div class="row mb-3">
							@foreach (array_keys(\App\Services\Ai\Moderation\Policy::CATEGORIES) as $code)
								<div class="col-md-6">
									<div class="form-check">
										<input class="form-check-input" type="checkbox" name="categories[]" value="{{ $code }}" id="cat-{{ $code }}" @checked(in_array($code, $selected, true))>
										<label class="form-check-label" for="cat-{{ $code }}">{{ \App\Services\Ai\Moderation\Policy::label($code) }}</label>
									</div>
								</div>
							@endforeach
						</div>
						<label for="reason" class="form-label fw-bold">{{ trans('ai.queue_reason') }}</label>
						<textarea name="reason" id="reason" class="form-control mb-1" rows="3" maxlength="300">{{ $reason }}</textarea>
						<div class="form-text mb-3">{{ trans('ai.queue_reason_hint') }}</div>
						<button type="submit" class="btn btn-danger">{{ trans('ai.queue_reject_submit') }}</button>
						<a href="{{ urlGen()->adminUrl('moderation') }}" class="btn btn-light">{{ trans('ai.queue_back') }}</a>
					</form>
				</div>
			</div>
		</div>
	</div>
@endsection
