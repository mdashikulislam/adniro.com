@php
	$startNowUrl = !doesGuestHaveAbilityToCreateListings() ? urlGen()->signInModal() : urlGen()->addPost();
@endphp
<div class="container mb-4">
	<div class="card bg-body-tertiary border text-secondary p-3">
		<div class="card-body text-center">
			<h3 class="fs-3 fw-bold">
				{{ t('do_you_have_anything') }}
			</h3>
			<h5 class="fs-5 mb-4">
				{{ t('sell_products_and_services_online_for_free') }}
			</h5>
			<a href="{!! $startNowUrl !!}" class="btn btn-primary px-3">
				{{ t('start_now') }}
			</a>
		</div>
	</div>
</div>
