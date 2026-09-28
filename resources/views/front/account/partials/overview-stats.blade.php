@php
	$userStats ??= [];

	$countPendingApprovalPosts = (int)data_get($userStats, 'posts.pendingApproval', 0);
	$countArchivedPosts = (int)data_get($userStats, 'posts.archived', 0);
	$countPosts = (int)data_get($userStats, 'posts.published', 0);
	$postsVisits = (int)data_get($userStats, 'posts.visits', 0);
	$countFavoritePosts = (int)data_get($userStats, 'posts.favourite', 0);
	$countThreads = (int)data_get($userStats, 'threads.all', 0);

	$statsData = [
		'activePosts' => [
			'icon'      => 'bi bi-megaphone-fill',
			'tone'      => 'teal',
			'countItem' => \App\Helpers\Common\Num::short($countPosts),
			'label'     => trans_choice('global.count_active_posts', getPlural($countPosts), [], config('app.locale')),
			'url'       => url(urlGen()->getAccountBasePath() . '/posts/list'),
		],
		'postsVisits' => [
			'icon'      => 'bi bi-eye-fill',
			'tone'      => 'blue',
			'countItem' => \App\Helpers\Common\Num::short($postsVisits),
			'label'     => trans_choice('global.count_visits', getPlural($postsVisits), [], config('app.locale')),
			'url'       => url(urlGen()->getAccountBasePath() . '/posts/list'),
		],
		'favoritePosts' => [
			'icon'      => 'bi bi-heart-fill',
			'tone'      => 'rose',
			'countItem' => \App\Helpers\Common\Num::short($countFavoritePosts),
			'label'     => trans_choice('global.count_favorites', getPlural($countFavoritePosts), [], config('app.locale')),
			'url'       => url(urlGen()->getAccountBasePath() . '/saved-posts'),
		],
		'messages' => [
			'icon'      => 'bi bi-chat-dots-fill',
			'tone'      => 'violet',
			'countItem' => \App\Helpers\Common\Num::short($countThreads),
			'label'     => trans_choice('global.count_mails', getPlural($countThreads), [], config('app.locale')),
			'url'       => url(urlGen()->getAccountBasePath() . '/messages'),
		],
		'pendingApprovalPosts' => [
			'icon'      => 'bi bi-hourglass-split',
			'tone'      => 'amber',
			'countItem' => \App\Helpers\Common\Num::short($countPendingApprovalPosts),
			'label'     => trans_choice('global.count_pending_approval_posts', getPlural($countPendingApprovalPosts), [], config('app.locale')),
			'url'       => url(urlGen()->getAccountBasePath() . '/posts/pending-approval'),
		],
		'archivedPosts' => [
			'icon'      => 'bi bi-archive-fill',
			'tone'      => 'slate',
			'countItem' => \App\Helpers\Common\Num::short($countArchivedPosts),
			'label'     => trans_choice('global.count_archived_posts', getPlural($countArchivedPosts), [], config('app.locale')),
			'url'       => url(urlGen()->getAccountBasePath() . '/posts/archived'),
		],
	];

	// Strip the leading number from the label ("3 listings" -> "listings")
	$cleanLabel = fn ($label, $count) => trim(preg_replace('/^\s*' . preg_quote((string)$count, '/') . '\s*/u', '', (string)$label));
@endphp
<div class="account-stats">
	<h2 class="account-section-title">
		<span class="account-section-icon"><i class="bi bi-bar-chart-fill"></i></span>
		{{ t('account_stats') }}
	</h2>
	<div class="account-stats-grid">
		@foreach($statsData as $key => $item)
			<a href="{{ $item['url'] }}" class="account-stat account-stat-{{ $item['tone'] }}">
				<span class="account-stat-icon"><i class="{{ $item['icon'] }}"></i></span>
				<span class="account-stat-body">
					<span class="account-stat-value">{{ $item['countItem'] }}</span>
					<span class="account-stat-label">{{ $cleanLabel($item['label'], $item['countItem']) }}</span>
				</span>
				<i class="bi bi-arrow-right account-stat-arrow"></i>
			</a>
		@endforeach
	</div>
</div>
