@php
	$authUser ??= null;
	$settings ??= collect();
	
	$generalSettingsAsSubmenu = (config('settings.app.general_settings_as_submenu_in_sidebar') == '1');
	$isGeneralSettingsAsSubmenuEnabled = ($generalSettingsAsSubmenu && $settings->count() > 0);
@endphp
@if (doesUserHavePermission($authUser, 'admin.setting.view') || userHasSuperAdminPermissions())
	@if ($isGeneralSettingsAsSubmenuEnabled)
		<li class="sidebar-item">
			<a href="#collapseGeneralSettings"
			   class="has-arrow sidebar-link"
			   data-bs-toggle="collapse"
			   aria-expanded="false"
			   aria-controls="collapseGeneralSettings"
			>
				<span class="hide-menu">{{ trans('admin.general_settings') }}</span>
			</a>
			<ul class="collapse second-level" id="collapseGeneralSettings">
				@foreach($settings as $setting)
					<li class="sidebar-item">
						<a href="{{ urlGen()->adminUrl('settings/' . $setting->id . '/edit') }}" class="sidebar-link">
							<span class="hide-menu">{{ $setting->label }}</span>
						</a>
					</li>
				@endforeach
				<li class="sidebar-item">&nbsp;</li>
			</ul>
		</li>
	@else
		<li class="sidebar-item">
			<a href="{{ urlGen()->adminUrl('settings') }}" class="sidebar-link">
				<span class="hide-menu">{{ trans('admin.general_settings') }}</span>
			</a>
		</li>
	@endif
@endif
