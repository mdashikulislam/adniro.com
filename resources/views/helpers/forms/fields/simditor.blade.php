{{-- simditor (WYSIWYG Editor) --}}
{{-- https://simditor.tower.im --}}
{{-- https://simditor.tower.im/docs/doc-usage.html --}}
{{-- https://simditor.tower.im//docs/doc-config.html --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'simditor';
	$type = 'textarea';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$allowsLinks ??= (
		isFromAdminPanel()
		|| (
			config('settings.listing_form.remove_url_before') != '1' &&
			config('settings.listing_form.remove_url_after') != '1'
		)
	);
	$height ??= 300;
	$pluginOptions ??= [];
	
	$locale = $pluginOptions['locale'] ?? app()->getLocale();
	$defaultImage = $pluginOptions['defaultImage'] ?? asset('assets/plugins/simditor/images/image.png');
	$elPreviewContainer = $pluginOptions['elPreviewContainer'] ?? '#preview';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'simditor');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<textarea
					id="simditor_{{ $id }}"
					name="{{ $name }}"
					@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
		            @include('helpers.forms.attributes.field')
		    >{{ $value }}</textarea>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	<link href="{{ asset('assets/plugins/simditor/styles/simditor.css') }}" media="all" rel="stylesheet" type="text/css"/>
	@if (config('lang.direction') == 'rtl')
		<link media="all" rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/simditor/styles/simditor-rtl.css') }}" />
	@endif
	<style>
		.simditor .simditor-wrapper > textarea, .simditor .simditor-body {
			min-height: {{ (int)$height }}px;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset('assets/plugins/simditor/scripts/mobilecheck.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/module.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/hotkeys.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/dompurify.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/simditor.js') }}"></script>
	<style>
		/* Bootstrap 5 */
		.simditor:has(textarea),
		.simditor .simditor-wrapper {
			border-radius: 6px;
		}
		.simditor .simditor-toolbar {
			border-top-left-radius: 6px;
			border-top-right-radius: 6px;
		}
		.simditor:has(textarea.is-invalid) {
			border: 1px solid #f85359;
		}
		
		/* Dark Theme CSS */
		[data-bs-theme="dark"] .simditor .simditor-toolbar,
		[data-theme="dark"] .simditor .simditor-toolbar,
		html[theme="dark"] .simditor .simditor-toolbar {
			background: #000000;
		}
		[data-bs-theme="dark"] .simditor .simditor-wrapper,
		[data-theme="dark"] .simditor .simditor-wrapper,
		html[theme="dark"] .simditor .simditor-wrapper {
			background: #3a3939;
		}
		
		[data-bs-theme="dark"] .simditor .simditor-toolbar .toolbar-menu,
		[data-theme="dark"] .simditor .simditor-toolbar .toolbar-menu,
		html[theme="dark"] .simditor .simditor-toolbar .toolbar-menu {
			background: #3a3939;
		}
		[data-bs-theme="dark"] .simditor .simditor-toolbar > ul > li > .toolbar-item.active,
		[data-theme="dark"] .simditor .simditor-toolbar > ul > li > .toolbar-item.active,
		html[theme="dark"] .simditor .simditor-toolbar > ul > li > .toolbar-item.active {
			background: #3a3939;
		}
		[data-bs-theme="dark"] .simditor .simditor-toolbar > ul > li.menu-on .toolbar-item,
		[data-bs-theme="dark"] .simditor .simditor-toolbar .toolbar-menu:before,
		[data-theme="dark"] .simditor .simditor-toolbar > ul > li.menu-on .toolbar-item,
		[data-theme="dark"] .simditor .simditor-toolbar .toolbar-menu:before,
		html[theme="dark"] .simditor .simditor-toolbar > ul > li.menu-on .toolbar-item,
		html[theme="dark"] .simditor .simditor-toolbar .toolbar-menu:before {
			background: #3a3939;
		}
		
		[data-bs-theme="dark"] .simditor .simditor .simditor-toolbar .toolbar-menu.toolbar-menu-table .menu-create-table,
		[data-theme="dark"] .simditor .simditor-toolbar .toolbar-menu.toolbar-menu-table .menu-create-table,
		html[theme="dark"] .simditor .simditor-toolbar .toolbar-menu.toolbar-menu-table .menu-create-table {
			background: #000000;
		}
		[data-bs-theme="dark"] .simditor .simditor-toolbar .toolbar-menu.toolbar-menu-table .menu-create-table table td:before,
		[data-theme="dark"] .simditor .simditor-toolbar .toolbar-menu.toolbar-menu-table .menu-create-table table td:before,
		html[theme="dark"] .simditor .simditor-toolbar .toolbar-menu.toolbar-menu-table .menu-create-table table td:before {
			border: 1px solid #000000;
			background: #3a3939;
		}
		
		[data-bs-theme="dark"] .simditor .simditor-body table thead,
		[data-bs-theme="dark"] .simditor .editor-style table thead,
		[data-theme="dark"] .simditor .simditor-body table thead,
		[data-theme="dark"] .simditor .editor-style table thead,
		html[theme="dark"] .simditor .simditor-body table thead,
		html[theme="dark"] .simditor .editor-style table thead {
			background-color: #1e1d1d;
		}
		[data-bs-theme="dark"] .simditor .simditor-body table td.active,
		[data-bs-theme="dark"] .simditor .simditor-body table th.active,
		[data-bs-theme="dark"] .simditor .editor-style table td.active,
		[data-bs-theme="dark"] .simditor .editor-style table th.active,
		[data-theme="dark"] .simditor .simditor-body table td.active,
		[data-theme="dark"] .simditor .simditor-body table th.active,
		[data-theme="dark"] .simditor .editor-style table td.active,
		[data-theme="dark"] .simditor .editor-style table th.active,
		html[theme="dark"] .simditor .simditor-body table td.active,
		html[theme="dark"] .simditor .simditor-body table th.active,
		html[theme="dark"] .simditor .editor-style table td.active,
		html[theme="dark"] .simditor .editor-style table th.active {
			background: #000000;
		}
		
		[data-bs-theme="dark"] .simditor .simditor-body,
		[data-bs-theme="dark"] .simditor .editor-style,
		[data-bs-theme="dark"] .simditor .simditor-body p,
		[data-bs-theme="dark"] .simditor .simditor-body div,
		[data-bs-theme="dark"] .simditor .editor-style p,
		[data-bs-theme="dark"] .simditor .editor-style div,
		[data-theme="dark"] .simditor .simditor-body,
		[data-theme="dark"] .simditor .editor-style,
		[data-theme="dark"] .simditor .simditor-body p,
		[data-theme="dark"] .simditor .simditor-body div,
		[data-theme="dark"] .simditor .editor-style p,
		[data-theme="dark"] .simditor .editor-style div,
		html[theme="dark"] .simditor .simditor-body,
		html[theme="dark"] .simditor .editor-style,
		html[theme="dark"] .simditor .simditor-body p,
		html[theme="dark"] .simditor .simditor-body div,
		html[theme="dark"] .simditor .editor-style p,
		html[theme="dark"] .simditor .editor-style div {
			color: #fff;
		}
	</style>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	@php
		$editorI18n = trans('simditor', [], $locale);
		$editorI18nJson = '';
		if (!empty($editorI18n) && is_array($editorI18n)) {
			$editorI18nJson = collect($editorI18n)->toJson();
			$editorI18nJson = convertUTF8HtmlToAnsi($editorI18nJson);
		}
	@endphp
	<script>
		var simditorSelector = 'textarea[name="{{ $name }}"].simditor';
		{{-- var simditorSelector = 'textarea.simditor'; --}}
		var simditorLocale = '{{ $locale }}';
		
		{{-- i18n --}}
		@if (!empty($editorI18nJson))
			Simditor.i18n = {'{{ $locale }}': {!! $editorI18nJson !!}};
		@endif
		
		{{-- Toolbar --}}
		var simditorToolbar = [];
		simditorToolbar.push('bold');
		simditorToolbar.push('italic');
		simditorToolbar.push('underline');
		simditorToolbar.push('|');
		simditorToolbar.push('title');
		simditorToolbar.push('fontScale');
		simditorToolbar.push('color');
		simditorToolbar.push('|');
		simditorToolbar.push('ul');
		simditorToolbar.push('ol');
		simditorToolbar.push('|');
		simditorToolbar.push('table');
		@if ($allowsLinks)
			simditorToolbar.push('link');
			simditorToolbar.push('image');
		@endif
		simditorToolbar.push('|');
		simditorToolbar.push('alignment');
		simditorToolbar.push('indent');
		simditorToolbar.push('outdent');
		@if (isFromAdminPanel())
			simditorToolbar.push('|');
			simditorToolbar.push('code');
		@endif
		
		var simditorMobileToolbar = ['bold', 'italic', 'underline', 'ul', 'ol'];
		
		var simditorAllowedTags = [
			'br', 'span', 'img', 'b', 'strong', 'i', 'strike', 'u', 'font', 'p', 'ul',
			'ol', 'li', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4', 'hr', 'table'
		];
		@if ($allowsLinks)
			simditorAllowedTags.push('a');
		@endif
		
		{{-- Fake Code Separator --}}
		(function () {
			onDocumentReady((event) => {
				@if (!empty($editorI18nJson))
					Simditor.locale = simditorLocale;
				@endif
				
				if (mobilecheck()) {
					simditorToolbar = simditorMobileToolbar;
				}
				
				const placeholder = '{{ $placeholder }}';
				const defaultImage = '{{ $defaultImage }}';
				const elPreviewContainer = '{{ $elPreviewContainer }}';
				
				const simditorEl = $(simditorSelector);
				if (simditorEl.length > 0) {
					{{-- Options --}}
					const simditorOptions = {
						textarea: $(simditorSelector),
						/* placeholder: placeholder, */{{-- Floating issue on scroll or on browser resizing --}}
						toolbar: simditorToolbar,
						allowedTags: simditorAllowedTags,
						defaultImage: defaultImage,
						pasteImage: false,
						upload: false
					};
					
					{{-- Init. --}}
					const editor = new Simditor(simditorOptions);
					
					const previewEl = $(elPreviewContainer);
					if (previewEl.length > 0) {
						return editor.on('valuechanged', e => {
							return previewEl.html(editor.getValue());
						});
					}
				}
			});
		}).call(this);
	</script>
@endpush
