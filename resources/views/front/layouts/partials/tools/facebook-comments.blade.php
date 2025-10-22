@php
	$commentsAreDisabledByUser ??= false;
	$areCommentsActivated = (
		config('settings.listing_page.activation_facebook_comments')
		&& config('services.facebook.client_id')
		&& !$commentsAreDisabledByUser
	);
	$fbClientId = config('services.facebook.client_id');
	$locale = config('lang.iso_locale', 'en_US');
	
	$userThemePreference ??= 'light';
@endphp
@if ($areCommentsActivated)
	@include('front.common.spacer')
	
	<div class="container mb-4 mt-4">
		<div id="fb-root"></div>
		<script>
			(function (d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s);
				js.id = id;
				js.src = "//connect.facebook.net/{{ $locale }}/sdk.js#xfbml=1&version=v19.0&appId={{ $fbClientId }}";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
		</script>
		<div
				class="fb-comments"
				data-href="{{ request()->url() }}"
				data-width="100%"
				data-numposts="5"
				data-colorscheme="{{ $userThemePreference }}"
		></div>
	</div>
@endif
