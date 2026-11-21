{{-- adniro: "Install app" in the phone account menu, shown by public/assets/js/app/app-install.js (not in the installed app) --}}
<button type="button" class="dropdown-item adn-account-menu-item" data-adn-install data-adn-install-entry hidden>
	<span class="adn-account-menu-icon" aria-hidden="true"><i class="bi bi-download"></i></span>
	<span data-adn-install-label>{{ trans('app_install.install') }}</span>
</button>
