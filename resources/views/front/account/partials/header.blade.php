@php
	$headerTitle ??= t('overview');
	$userName = $authUser->name ?? '--';
	$userPhotoUrl = $authUser->photo_url ?? config('larapen.media.avatar');

	// Split a leading "<i ...></i>" icon from the title so it can sit in a tile
	$headerIcon = null;
	$headerText = $headerTitle;
	if (preg_match('/^\s*<i\s+class="([^"]+)"[^>]*><\/i>\s*(.*)$/su', $headerTitle, $m)) {
		$headerIcon = $m[1];
		$headerText = $m[2];
	}
@endphp
<div class="account-page-head">
	<div class="account-page-head-main">
		@if (!empty($headerIcon))
			<span class="account-page-head-icon"><i class="{{ $headerIcon }}"></i></span>
		@endif
		<div class="account-page-head-text">
			<h1 class="account-page-title">{!! $headerText !!}</h1>
			<div class="account-page-breadcrumb">{!! Breadcrumb::render() !!}</div>
		</div>
	</div>
	<div class="account-page-head-user d-none d-lg-flex">
		<span class="account-page-head-username">{{ $userName }}</span>
		<img id="userImg" class="account-page-head-avatar" src="{{ $userPhotoUrl }}" alt="user">
	</div>
</div>
