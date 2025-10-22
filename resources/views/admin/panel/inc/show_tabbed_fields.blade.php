@php
	$horizontalTabs = ($xPanel->getTabsType() == 'horizontal');
	
	$tabContainerClasses = $horizontalTabs ? '' : ' row d-flex align-items-start';
	$navColClass = $horizontalTabs ? '' : 'col-12 col-lg-3';
	$tabPaneColClass = $horizontalTabs ? '' : 'col-12 col-lg-9';
	$navTypeClass = $horizontalTabs ? 'nav-tabs' : 'nav-pills flex-column';
	$ariaOrientationAttr = $horizontalTabs ? '' : ' aria-orientation="vertical"';
	$bsToggle = $horizontalTabs ? 'tab' : 'pill';
	$fsClass = $horizontalTabs ? ' fs-5 fw-bold' : ' fs-5';
	$navTypeContentClass = $horizontalTabs ? ' w-100' : ' w-100 w-md-75';
	$tabPaneBorderClass = $horizontalTabs ? ' border border-top-0' : ' border rounded';
@endphp

@include('admin.panel.inc.show_fields', ['fields' => $xPanel->getFieldsWithoutATab()])

<div class="col-12 mt-3">
	<div class="tab-container{{ $tabContainerClasses }}">
		<div class="{{ $navColClass }}">
			<ul class="nav {{ $navTypeClass }} nav-tabs-custom" id="formTabs" role="tablist"{!! $ariaOrientationAttr !!}>
				@foreach ($xPanel->getTabs() as $k => $tab)
					@php
						$tabSlug = str($tab)->slug('')->toString();
						$activeClass = ($k == 0) ? ' active' : '';
						// $ariaCurrentAttr = ($k == 0) ? ' aria-current="page"' : '';
						$ariaSelected = ($k == 0) ? 'true' : 'false';
					@endphp
					<li class="nav-item" role="presentation">
						<a class="nav-link{{ $activeClass . $fsClass }}"
						   id="{{ $tabSlug }}-tab"
						   data-bs-toggle="{{ $bsToggle }}"
						   data-bs-target="#{{ $tabSlug }}-tab-pane"
						   role="tab"
						   aria-controls="{{ $tabSlug }}-tab-pane"
						   aria-selected="{{ $ariaSelected }}"
						   data-tab-name="{{ $tabSlug }}"
						   style="cursor: pointer;"
						>{{ $tab }}</a>
					</li>
				@endforeach
			</ul>
		</div>
		<div class="{{ $tabPaneColClass }}">
			<div class="tab-content{{ $navTypeContentClass }}" id="formTabContent">
				@foreach ($xPanel->getTabs() as $k => $tab)
					@php
						$tabSlug = str($tab)->slug('')->toString();
						$activeClass = ($k == 0) ? ' show active' : '';
					@endphp
					<div class="tab-pane fade{{ $activeClass . $tabPaneBorderClass }} pt-3"
					     id="{{ $tabSlug }}-tab-pane"
					     role="tabpanel"
					     aria-labelledby="{{ $tabSlug }}-tab"
					     tabindex="0"
					>
						<div class="container">
							<div class="row">
								@include('admin.panel.inc.show_fields', ['fields' => $xPanel->getTabFields($tab)])
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
	</div>
</div>

@push('crud_fields_styles')
	<style>
		#formTabs h1,
		#formTabs h2,
		#formTabs h3 {
			border-bottom: initial;
			margin-top: initial;
			padding-bottom: initial;
		}
		
		.tab-pane .row div:first-of-type > h1,
		.tab-pane .row div:first-of-type > h2,
		.tab-pane .row div:first-of-type > h3 {
			margin-top: 0;
			font-weight: bold;
		}
		.tab-pane .row div:not(:first-of-type) > h1,
		.tab-pane .row div:not(:first-of-type) > h2,
		.tab-pane .row div:not(:first-of-type) > h3 {
			/* reset other h1,h2,h3 elements */
		}
	</style>
@endpush
