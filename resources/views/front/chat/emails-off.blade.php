{{--
	adniro: the page behind the "stop these emails" link of the unread-messages email. It asks first (mail
	scanners open every link), then confirms. No sign-in is needed: the address is signed for one member.
--}}
@extends('front.layouts.master')

@section('content')
	<div class="main-container">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-6 col-md-8">
					<div class="card my-4">
						<div class="card-body p-4 text-center">
							@if ($isDone)
								<h1 class="h4 fw-bold mb-2">{{ trans('chat.emails_off_done_title') }}</h1>
								<p class="text-muted mb-4">{{ trans('chat.emails_off_done_text') }}</p>
								<a href="{{ route('chat.index') }}" class="btn btn-primary">{{ trans('chat.digest_action') }}</a>
							@else
								<h1 class="h4 fw-bold mb-2">{{ trans('chat.emails_off_title') }}</h1>
								<p class="text-muted mb-4">{{ trans('chat.emails_off_text') }}</p>
								<form method="POST" action="{{ $offUrl }}">
									@csrf
									<button type="submit" class="btn btn-primary">{{ trans('chat.emails_off_confirm') }}</button>
								</form>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
