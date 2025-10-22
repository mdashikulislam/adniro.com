@php
	$thread ??= [];
	$isLastThread ??= false;
	
	$userName = data_get($thread, 'p_creator.name');
	$avatarUrl = url(data_get($thread, 'p_creator.photo_url', ''));
	$userIsOnline = isUserOnline(data_get($thread, 'p_creator')) ? 'online text-success' : 'offline text-secondary';
	
	$msgUri = urlGen()->getAccountBasePath() . '/messages/' . data_get($thread, 'id');
	$msgSubject = data_get($thread, 'subject');
	$msgBody = str(data_get($thread, 'latest_message.body') ?? '')->limit(125);
	$msgCreatedAt = data_get($thread, 'created_at_formatted', data_get($thread, 'created_at')); // not sent
	$isImportant = data_get($thread, 'p_is_important');
	$isUnread = data_get($thread, 'p_is_unread');
	
	$borderBottom = !$isLastThread ? ' border-bottom pb-2' : '';
	$unreadClass = $isUnread ? ' bg-warning-subtle fw-bold' : '';
@endphp
<div class="row hstack gap-0{{ $unreadClass . $borderBottom }} mb-2">
	<div class="col-auto">
		<input type="checkbox" name="entries[]" value="{{ data_get($thread, 'id') }}">
	</div>
	
	<div class="col-2">
		<a href="{{ url($msgUri) }}" class="list-box-user">
			<img src="{{ $avatarUrl }}" class="img-fluid object-fit-fill border rounded" alt="{{ $userName }}">
		</a>
	</div>
	
	<div class="col-8">
		<a href="{{ url($msgUri) }}" class="list-box-content {{ linkClass('body-emphasis') }}">
			<h5 class="fs-5 fw-bold mt-0">{{ $msgSubject }}</h5>
			<span class="fs-6">
				<i class="fa-solid fa-circle {{ $userIsOnline }}"></i> {{ $userName }}
			</span>
			<div class="">
				{{ $msgBody }}
			</div>
			<div class="text-muted">{{ $msgCreatedAt }}</div>
		</a>
	</div>
	
	<div class="col-1 ms-auto list-box-action">
		<div class="row d-flex flex-column text-end">
			<div class="col-12">
				<div class="btn-group-vertical" role="group" aria-label="Vertical button group">
					{{-- Mark as Important --}}
					@if ($isImportant)
						<a href="{{ url($msgUri . '/actions?type=markAsNotImportant') }}"
						   data-bs-toggle="tooltip"
						   data-bs-placement="top"
						   class="markAsNotImportant btn btn-light btn-sm"
						   title="{{ t('Mark as not important') }}"
						>
							<i class="fa-solid fa-star"></i>
						</a>
					@else
						<a href="{{ url($msgUri . '/actions?type=markAsImportant') }}"
						   data-bs-toggle="tooltip"
						   data-bs-placement="top"
						   class="markAsImportant btn btn-light btn-sm"
						   title="{{ t('Mark as important') }}"
						>
							<i class="fa-regular fa-star"></i>
						</a>
					@endif
					
					{{-- Delete --}}
					<a href="{{ url($msgUri . '/delete') }}"
					   data-bs-toggle="tooltip"
					   data-bs-placement="top"
					   class="btn btn-light btn-sm"
					   title="{{ t('Delete') }}"
					>
						<i class="fa-solid fa-trash"></i>
					</a>
					
					{{-- Mask as Read --}}
					@if ($isUnread)
						<a href="{{ url($msgUri . '/actions?type=markAsRead') }}"
						   data-bs-toggle="tooltip"
						   data-bs-placement="top"
						   class="markAsRead btn btn-light btn-sm"
						   title="{{ t('Mark as read') }}"
						>
							<i class="fa-solid fa-envelope"></i>
						</a>
					@else
						<a href="{{ url($msgUri . '/actions?type=markAsUnread') }}"
						   data-bs-toggle="tooltip"
						   data-bs-placement="top"
						   class="markAsRead btn btn-light btn-sm"
						   title="{{ t('Mark as unread') }}"
						>
							<i class="fa-solid fa-envelope-open"></i>
						</a>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
