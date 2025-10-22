@php
	/** @var \App\Models\Page $model (for example) */
	$model = $xPanel->model;
@endphp
<form role="form" novalidate>
	
    {{-- Show the inputs --}}
	<div class="container px-0 mb-0">
		<div class="row">
			{{-- See if we're using tabs --}}
			@if ($xPanel->tabsEnabled() && count($xPanel->getTabs()) > 0)
				@php
					// Get the first tab as default tab
					$defaultTab = $xPanel->getTabs()[0];
					$defaultTab = str($defaultTab)->slug('')->toString();
				@endphp
				@include('admin.panel.inc.show_tabbed_fields')
				<input type="hidden" name="current_tab" value="{{ $defaultTab }}" />
			@else
				@include('admin.panel.inc.show_fields', ['fields' => $fields])
			@endif
		</div>
	</div>
	
</form>

{{-- Define blade stacks so css and js can be pushed from the fields to these sections. --}}

@section('after_styles')
	@parent
	
	<link rel="stylesheet" href="{{ asset('assets/admin/crud/css/crud.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/crud/css/form.css') }}">
	<link rel="stylesheet" href="{{ asset("assets/admin/crud/css/{$action}.css") }}">
	
    <!-- CRUD FORM CONTENT - crud_fields_styles stack -->
    @stack('crud_fields_styles')
@endsection

@section('after_scripts')
	@parent
	
	<script src="{{ asset('assets/admin/crud/js/crud.js') }}"></script>
	<script src="{{ asset('assets/admin/crud/js/form.js') }}"></script>
	<script src="{{ asset("assets/admin/crud/js/{$action}.js") }}"></script>
	
    <!-- CRUD FORM CONTENT - crud_fields_scripts stack -->
    @stack('crud_fields_scripts')

    <script>
	    onDocumentReady((event) => {
			/* Enable tabbable tabs via JavaScript (each tab needs to be activated individually) */
		    const triggerTabList = document.querySelectorAll('#formTabs a, #formTabs button');
		    triggerTabList.forEach(triggerEl => {
			    const tabTrigger = new bootstrap.Tab(triggerEl);
			    
			    triggerEl.addEventListener('click', event => {
				    event.preventDefault()
				    tabTrigger.show()
			    });
		    });
		    
			/* Save button has multiple actions: save and exit, save and edit, save and new */
			const saveActions = $('#saveActions');
		    const crudForm = saveActions.parents('form');
		    const saveActionField = $('[name="save_action"]');
			
			saveActions.on('click', '.dropdown-menu a', function() {
				const saveAction = $(this).data('value');
				saveActionField.val(saveAction);
				crudForm.submit();
			});
			
            /* Ctrl+S and Cmd+S trigger Save button click */
            $(document).keydown(function(e) {
                if (
					((e.which === 115 || e.which === '115') || (e.which === 83 || e.which === '83'))
	                && (e.ctrlKey || e.metaKey)
                )
                {
                    e.preventDefault();
                    /* alert("Ctrl-s pressed"); */
                    $("button[type=submit]").trigger('click');
                    return false;
                }
                return true;
            });
		    
		    /* Prevent duplicate entries on double-clicking the submit form */
		    crudForm.submit(function (event) {
			    $("button[type=submit]").prop('disabled', true);
		    });
			
			/* Place the focus on the first element in the form */
            @if ($xPanel->autoFocusOnFirstField)
	            @php
	                $focusField = \Illuminate\Support\Arr::first($fields, function($field) {
	                    return isset($field['auto_focus']) && $field['auto_focus'] == true;
	                })
	            @endphp
				
	            @if ($focusField)
		            @php
			            $focusFieldName = !is_iterable($focusField['value']) ? $focusField['name'] : ($focusField['name'] . '[]');
		            @endphp
	                window.focusField = $('[name="{{ $focusFieldName }}"]').eq(0);
	            @else
	                const focusField = $('form').find('input, textarea, select').not('[type="hidden"]').eq(0);
	            @endif
				
			    const fieldOffset = focusField.offset().top;
			    const scrollTolerance = $(window).height() / 2;
				
	            focusField.trigger('focus');
				
	            if (fieldOffset > scrollTolerance) {
	                $('html, body').animate({scrollTop: (fieldOffset - 30)});
	            }
            @endif
		    
		    /* Add inline errors to the DOM */
		    @if ($xPanel->inlineErrorsEnabled())
			    @if (isset($errors) && $errors->any())
				    window.errorList = {!! json_encode($errors->messages()) !!};
					const isTabsEnabled = {{ $xPanel->tabsEnabled() ? 'true' : 'false' }};
			        /* console.error(window.errorList); */
		            
		            {{--
				    $.each(errorList, function(property, messages) {
					    const normalizedProperty = property.split('.')
						    .map(function(item, index) {
							    return index === 0 ? item : '[' + item + ']';
						    }).join('');
					    
						let $field = $('[name="' + normalizedProperty + '[]"]');
					    $field = $field.length ? $field : $('[name="' + normalizedProperty + '"]');
						
					    const container = $field.parents('div.mb-3');
					    container.addClass('is-invalid');
					    
					    $.each(messages, function(key, msg) {
						    /* highlight the input that errored */
						    const row = $('<div class="invalid-feedback">' + msg + '</div>');
						    row.appendTo(container);
						    
						    /* highlight its parent tab */
						    if (isTabsEnabled) {
							    const tabPaneId = $(container).parents('.tab-pane').attr('id');
							    $("#formTabs [aria-controls=" + tabPaneId + "]").addClass('text-danger');
						    }
					    });
				    });
				    --}}
		            
		            let savedOpenTab = null;
				    Object.entries(errorList).forEach(([property, messages]) => {
					    const normalizedProperty = property.split('.')
						    .map((item, index) => {
							    return index === 0 ? item : '[' + item + ']';
						    }).join('');
					    
					    let field = document.querySelector('[name="' + normalizedProperty + '[]"]');
					    field = field ? field : document.querySelector('[name="' + normalizedProperty + '"]');
					    
					    const container = field.closest('div.mb-3');
						if (container) {
							container.classList.add('is-invalid');
							
							messages.forEach((msg) => {
								/* highlight the input that errored */
								const row = document.createElement('div');
								row.className = 'invalid-feedback';
								row.textContent = msg;
								container.appendChild(row);
								
								/* highlight its parent tab */
								if (isTabsEnabled) {
									const tabPane = container.closest('.tab-pane');
									if (tabPane) {
										const tabPaneId = tabPane.getAttribute('id');
										const tabEl = document.querySelector("#formTabs [aria-controls=" + tabPaneId + "]");
										if (tabEl) {
											tabEl.classList.add('text-danger');
											
											/* Activate (Open) the first tab containing error fields */
											if (savedOpenTab === null) {
												const tab = new bootstrap.Tab(tabEl);
												tab.show();
												
												savedOpenTab = tabEl.getAttribute('data-tab-name');
											}
										}
									}
								}
							});
						}
				    });
		       @endif
		    @endif
		       
	        {{--
		     * The [data-bs-toggle] selector match all the selectors below:
		     * - button[data-bs-toggle="tab"]
		     * - button[data-bs-toggle="pill"]
		     * - a[data-bs-toggle="tab"]
		     * - a[data-bs-toggle="pill"]
		     --}}
		    const tabSelector = '#formTabs [data-bs-toggle]';
	        initTabsWithUrlHash(tabSelector, '.tab-container');
		    
			/* Update the 'current_tab' hidden field value on new tab activated */
			const currentTabEl = document.querySelector('input[name="current_tab"]');
			if (currentTabEl) {
				const tabElements = document.querySelectorAll(tabSelector);
				tabElements.forEach(function(element) {
					element.addEventListener('shown.bs.tab', event => {
						currentTabEl.value = event.target.getAttribute('data-tab-name');
					});
				});
			}
        });
    </script>
@endsection
