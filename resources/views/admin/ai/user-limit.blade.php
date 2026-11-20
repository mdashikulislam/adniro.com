{{-- Admin > Users > AI post limit (adniro:ai) --}}
@extends('admin.layouts.master')

@section('header')
	<div class="row page-titles">
		<div class="col-12 align-self-center">
			<h2 class="mb-0">{{ trans('ai.user_limit_title') }}</h2>
		</div>
	</div>
@endsection

@section('content')
	<div class="row">
		<div class="col-lg-6 col-12">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">{{ $user->name }} <small class="text-muted">#{{ $user->id }} · {{ $user->email }}</small></h4>
					
					<p class="mb-3">
						@if ($status->unlimited())
							{{ trans('ai.user_limit_status_unlimited') }}
						@else
							{{ trans('ai.user_limit_status', [
								'used'   => $status->used,
								'limit'  => $status->limit,
								'period' => trans('ai.period_' . $status->period),
								'when'   => $status->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL'),
							]) }}
						@endif
					</p>
					
					@if ($errors->any())
						<div class="alert alert-danger">{{ $errors->first() }}</div>
					@endif
					
					<form method="post" action="{{ urlGen()->adminUrl('users/' . $user->id . '/ai-post-limit') }}" class="mb-3">
						@csrf
						<label for="aiPostLimit" class="form-label fw-bold">{{ trans('ai.user_limit_label') }}</label>
						<input type="number" min="0" step="1" name="ai_post_limit" id="aiPostLimit" class="form-control mb-1" value="{{ old('ai_post_limit', $user->ai_post_limit) }}">
						<div class="form-text mb-3">{{ trans('ai.user_limit_hint') }}</div>
						<button type="submit" name="action" value="save" class="btn btn-primary">{{ trans('ai.user_limit_save') }}</button>
					</form>
					
					<form method="post" action="{{ urlGen()->adminUrl('users/' . $user->id . '/ai-post-limit') }}">
						@csrf
						<button type="submit" name="action" value="reset" class="btn btn-outline-secondary">{{ trans('ai.user_limit_reset') }}</button>
						<div class="form-text">{{ trans('ai.user_limit_reset_hint') }}</div>
					</form>
					
					<a href="{{ urlGen()->adminUrl('users') }}" class="btn btn-light mt-3">{{ trans('ai.user_limit_back') }}</a>
				</div>
			</div>
		</div>
	</div>
@endsection
