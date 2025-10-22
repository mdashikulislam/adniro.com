@php
	use App\Helpers\Common\Date\TimeZoneManager;
	use Illuminate\Support\Carbon;
	
	$authUser = auth()->check() ? auth()->user() : null;
	$authUserId = !empty($authUser) ? $authUser->getAuthIdentifier() : 0;
	
	$thread ??= [];
	$message ??= [];
	
	$filePath = data_get($message, 'file_path');
@endphp
@if ($authUserId == data_get($message, 'user.id'))
	<div class="row mb-3 d-flex justify-content-end chat-item object-me">
		<div class="col-8 text-end chat-item-content">
			<div class="msg bg-success-subtle rounded-4 rounded-end-0 rounded px-3 py-2">
				{!! urlsToLinks(nlToBr(data_get($message, 'body')), ['class' => linkClass()]) !!}
				@if (!empty($filePath) && $disk->exists($filePath))
					@php
						$mt2Class = !empty(trim(data_get($message, 'body'))) ? ' mt-2' : '';
					@endphp
					<div class="{{ $mt2Class }}">
						<i class="fa-solid fa-paperclip" aria-hidden="true"></i>
						<a class="{{ linkClass() }}"
						   href="{{ privateFileUrl($filePath, null) }}"
						   target="_blank"
						   data-bs-toggle="tooltip"
						   data-bs-placement="left"
						   title="{{ basename($filePath) }}"
						>
							{{ str($filePath)->basename()->limit(20) }}
						</a>
					</div>
				@endif
			</div>
			<span class="small text-secondary time-and-date">
				{{ data_get($message, 'created_at_formatted') }}
				@php
					$recipient = data_get($message, 'p_recipient');
					$tz = TimeZoneManager::getContextualTimeZone();
					
					$threadUpdatedAt = new Carbon(data_get($thread, 'updated_at'));
					$threadUpdatedAt->timezone($tz);
					
					$recipientLastRead = new Carbon(data_get($recipient, 'last_read'));
					$recipientLastRead->timezone($tz);
					
					$threadIsUnreadByThisRecipient = (
						!empty($recipient)
						&& (
							data_get($recipient, 'last_read') === null
							|| $threadUpdatedAt->gt($recipientLastRead)
						)
					);
				@endphp
				@if ($threadIsUnreadByThisRecipient)
					&nbsp;<i class="fa-solid fa-check-double"></i>
				@endif
			</span>
		</div>
	</div>
@else
	<div class="row mb-3 d-flex justify-content-start chat-item object-user">
		<div class="col-2 object-user-img">
			<a href="{{ urlGen()->user(data_get($message, 'user')) }}">
				<img src="{{ url(data_get($message, 'user.photo_url')) }}"
				     class="img-fluid object-fit-fill rounded-circle"
				     alt="{{ data_get($message, 'user.name') }}"
				>
			</a>
		</div>
		<div class="col-8 chat-item-content">
			<div class="chat-item-content-inner">
				<div class="msg bg-body-secondary rounded-4 rounded-start-0 px-3 py-2">
					{!! urlsToLinks(nlToBr(data_get($message, 'body')), ['class' => linkClass()]) !!}
					@if (!empty($filePath) && $disk->exists($filePath))
						@php
							$mt2Class = !empty(trim(data_get($message, 'body'))) ? 'mt-2' : '';
						@endphp
						<div class="{{ $mt2Class }}">
							<i class="fa-solid fa-paperclip" aria-hidden="true"></i>
							<a class="{{ linkClass() }}"
							   href="{{ privateFileUrl($filePath, null) }}"
							   target="_blank"
							   data-bs-toggle="tooltip"
							   data-bs-placement="left"
							   title="{{ basename($filePath) }}"
							>
								{{ str($filePath)->basename()->limit(20) }}
							</a>
						</div>
					@endif
				</div>
				@php
					$userIsOnline = isUserOnline(data_get($message, 'user'));
				@endphp
				<span class="small text-secondary time-and-date ms-0">
					@if ($userIsOnline)
						<i class="fa-solid fa-circle color-success"></i>&nbsp;
					@endif
					{{ data_get($message, 'created_at_formatted') }}
				</span>
			</div>
		</div>
	</div>
@endif
