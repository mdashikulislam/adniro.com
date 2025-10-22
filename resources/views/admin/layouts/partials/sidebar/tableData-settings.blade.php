@php
	$authUser ??= null;
@endphp
@if (doesUserHavePermission($authUser, 'admin.language.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('languages') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('admin.languages') }}</span>
		</a>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.menu.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('menus') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('menu.menus') }}</span>
		</a>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.section.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="#collapseHomepage"
		   class="sidebar-link has-arrow"
		   data-bs-toggle="collapse"
		   aria-expanded="false"
		   aria-controls="collapseHomepage"
		>
			<i class="mdi mdi-adjust"></i> <span class="hide-menu">{{ trans('admin.homepage') }}</span>
		</a>
		<ul class="collapse second-level" id="collapseHomepage">
			<li class="sidebar-item">
				<a href="{{ urlGen()->adminUrl('homepage/presets') }}" class="sidebar-link">
					<i class="mdi mdi-adjust"></i>
					<span class="hide-menu">{{ trans('admin.presets') }}</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a href="{{ urlGen()->adminUrl('homepage/sections') }}" class="sidebar-link">
					<i class="mdi mdi-adjust"></i>
					<span class="hide-menu">{{ trans('admin.sections') }}</span>
				</a>
			</li>
			<li class="sidebar-item">&nbsp;</li>
		</ul>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.meta-tag.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('meta_tags') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('admin.meta tags') }}</span>
		</a>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.package.view')|| userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="#collapsePackages"
		   class="sidebar-link has-arrow"
		   data-bs-toggle="collapse"
		   aria-expanded="false"
		   aria-controls="collapsePackages"
		>
			<i class="mdi mdi-adjust"></i> <span class="hide-menu">{{ trans('admin.packages') }}</span>
		</a>
		<ul class="collapse second-level" id="collapsePackages">
			<li class="sidebar-item">
				<a href="{{ urlGen()->adminUrl('packages/promotion') }}" class="sidebar-link">
					<i class="mdi mdi-adjust"></i>
					<span class="hide-menu">{{ trans('admin.promotion') }}</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a href="{{ urlGen()->adminUrl('packages/subscription') }}" class="sidebar-link">
					<i class="mdi mdi-adjust"></i>
					<span class="hide-menu">{{ trans('admin.subscription') }}</span>
				</a>
			</li>
			<li class="sidebar-item">&nbsp;</li>
		</ul>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.payment-method.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('payment_methods') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('admin.payment methods') }}</span>
		</a>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.advertising.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('advertisings') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('admin.advertising') }}</span>
		</a>
	</li>
@endif
@if (
	doesUserHavePermission($authUser, 'admin.country.view')
	|| doesUserHavePermission($authUser, 'admin.currency.view')
	|| userHasSuperAdminPermissions()
)
	<li class="sidebar-item">
		<a href="#collapseInternational"
		   class="sidebar-link has-arrow"
		   data-bs-toggle="collapse"
		   aria-expanded="false"
		   aria-controls="collapseInternational"
		>
			<i class="fa-solid fa-globe"></i> <span class="hide-menu">{{ trans('admin.international') }}</span>
		</a>
		<ul class="collapse second-level" id="collapseInternational">
			@if (doesUserHavePermission($authUser, 'admin.country.view') || userHasSuperAdminPermissions())
				<li class="sidebar-item">
					<a href="{{ urlGen()->adminUrl('countries') }}" class="sidebar-link">
						<i class="mdi mdi-adjust"></i>
						<span class="hide-menu">{{ trans('admin.countries') }}</span>
					</a>
				</li>
			@endif
			@if (doesUserHavePermission($authUser, 'admin.currency.view') || userHasSuperAdminPermissions())
				<li class="sidebar-item">
					<a href="{{ urlGen()->adminUrl('currencies') }}" class="sidebar-link">
						<i class="mdi mdi-adjust"></i>
						<span class="hide-menu">{{ trans('admin.currencies') }}</span>
					</a>
				</li>
			@endif
			<li class="sidebar-item">&nbsp;</li>
		</ul>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.blacklist.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('blacklists') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('admin.blacklist') }}</span>
		</a>
	</li>
@endif
@if (doesUserHavePermission($authUser, 'admin.report-type.view') || userHasSuperAdminPermissions())
	<li class="sidebar-item">
		<a href="{{ urlGen()->adminUrl('report_types') }}" class="sidebar-link">
			<i class="mdi mdi-adjust"></i>
			<span class="hide-menu">{{ trans('admin.report types') }}</span>
		</a>
	</li>
@endif
