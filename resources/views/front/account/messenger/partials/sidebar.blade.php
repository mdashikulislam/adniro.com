@php
	$stats ??= [];
	$countThreadsWithNewMessage = (int)data_get($stats, 'threads.withNewMessage'); // not sent
	
	$navLinks = [
		'inbox' => [
			'label'    => t('inbox'),
			'url'      => url(urlGen()->getAccountBasePath() . '/messages'),
			'isActive' => (!request()->has('filter') || request()->query('filter')==''),
		],
		'unread' => [
			'label'    => t('unread'),
			'url'      => url(urlGen()->getAccountBasePath() . '/messages?filter=unread'),
			'isActive' => (request()->query('filter')=='unread'),
		],
		'started' => [
			'label'    => t('started'),
			'url'      => url(urlGen()->getAccountBasePath() . '/messages?filter=started'),
			'isActive' => (request()->query('filter')=='started'),
		],
		'important' => [
			'label'    => t('important'),
			'url'      => url(urlGen()->getAccountBasePath() . '/messages?filter=important'),
			'isActive' => (request()->query('filter')=='important'),
		],
	];
@endphp
<div class="col-md-3 col-lg-2">
	<ul class="nav nav-pills nav-justified inbox-nav">
		@foreach($navLinks as $key => $item)
			@php
				$activeClass = $item['isActive'] ? ' active' : '';
				$linkUrl = $item['url'];
				$linkLabel = $item['label'];
				$activeLinkClass = $item['isActive'] ? 'text-white' : 'link-primary';
			
				$hasBadge = ($key == 'inbox');
				$badgeColor = ' ' . ($item['isActive'] ? 'text-bg-light' : 'text-bg-primary');
				$badgeVisibility = ($countThreadsWithNewMessage <= 0) ? ' d-none' : '';
				$badgeVisibility = '';
			@endphp
			<li class="nav-item">
				<a class="nav-link{{ $activeClass }}" href="{{ $linkUrl }}">
					{{ $linkLabel }}
					@if ($hasBadge)
						<span class="count-threads-with-new-messages count badge rounded-pill {{ $badgeColor . $badgeVisibility }}">
							{{ \App\Helpers\Common\Num::short($countThreadsWithNewMessage) }}
						</span>
					@endif
				</a>
			</li>
		@endforeach
	</ul>
</div>
