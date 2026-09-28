@php
	$startNowUrl = !doesGuestHaveAbilityToCreateListings() ? urlGen()->signInModal() : urlGen()->addPost();
@endphp
<div class="container mb-4">
	<section class="sell-cta">
		<span class="sell-cta-glow sell-cta-glow-1" aria-hidden="true"></span>
		<span class="sell-cta-glow sell-cta-glow-2" aria-hidden="true"></span>
		<div class="sell-cta-body">
			<div class="sell-cta-icon" aria-hidden="true">
				<i class="bi bi-megaphone-fill"></i>
			</div>
			<div class="sell-cta-text">
				<h3 class="sell-cta-title">{{ t('do_you_have_anything') }}</h3>
				<p class="sell-cta-subtitle">{{ t('sell_products_and_services_online_for_free') }}</p>
				<ul class="sell-cta-perks list-unstyled">
					<li><i class="bi bi-check-circle-fill"></i> {{ t('Free') }}</li>
					<li><i class="bi bi-lightning-charge-fill"></i> {{ t('Takes 2 minutes') }}</li>
					<li><i class="bi bi-people-fill"></i> {{ t('Reach local buyers') }}</li>
				</ul>
			</div>
			<a href="{!! $startNowUrl !!}" class="btn sell-cta-btn">
				<span>{{ t('start_now') }}</span>
				<i class="bi bi-arrow-right"></i>
			</a>
		</div>
	</section>
</div>
