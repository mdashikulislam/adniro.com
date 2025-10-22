{{-- ckeditor (WYSIWYG Editor) --}}
{{-- https://github.com/ckeditor/ckeditor5 --}}
{{-- https://ckeditor.com/docs/ckeditor5/latest/getting-started/installation/self-hosted/quick-start.html --}}
{{-- https://ckeditor.com/docs/ckeditor5/latest/examples/builds/classic-editor.html --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'ckeditor';
	$type = 'textarea'; // ckeditor
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
	$height ??= 350;
	$pluginOptions ??= [];
	
	$language = $pluginOptions['language'] ?? app()->getLocale();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'ckeditor');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			<textarea
					id="ckeditor_{{ $id }}"
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
	$pluginBasePath = 'assets/plugins/ckeditor/';
	$pluginFullPath = public_path($pluginBasePath);
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	<style>
		/* Editor's Height */
		.ck-editor__editable_inline {
			min-height: {{ (int)$height }}px;
		}
		/*
		 * Default CSS Values for HTML Elements
		 * to prevent the editor's CSS overwrite
		 */
		.ck ul {
			list-style-type: disc;
		}
		.ck ol {
			list-style-type: decimal;
		}
		.ck ul, .ck ol {
			list-style-position: inside;
			display: block;
			margin-top: 1em;
			margin-bottom: 1em;
			margin-left: 0;
			margin-right: 0;
			padding-left: 40px;
		}
		.ck ul li {
			list-style-type: disc;
		}
		.ck ol li {
			list-style-type: decimal;
		}
		.ck ul ul, .ck ol ul {
			list-style-type: circle;
		}
		.ck ol ol, .ck ul ol {
			list-style-type: lower-latin;
		}
		.ck li {
			display: list-item;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset($pluginBasePath . 'ckeditor.js') }}"></script>
	
	@php
		$localeFilesBasePath = $pluginBasePath . 'translations/';
		$localeFilesFullPath = public_path($localeFilesBasePath);
		
		$foundLocale = '';
		if (file_exists($localeFilesFullPath . getLangTag($language) . '.js')) {
			$foundLocale = getLangTag($language);
		}
		if (empty($foundLocale)) {
			if (file_exists($localeFilesFullPath . strtolower($language) . '.js')) {
				$foundLocale = strtolower($language);
			}
		}
		if (empty($foundLocale)) {
			$foundLocale = 'en';
		}
	@endphp
	@if ($foundLocale != 'en')
		<script src="{{ asset($localeFilesBasePath . $foundLocale . '.js') }}"></script>
	@endif
	
	<script>
		{{-- var ckeditorSelector = 'textarea[name="{{ $name }}"].ckeditor'; --}}
		var ckeditorSelector = 'textarea.ckeditor';
		
		{{-- Toolbar --}}
		var toolbarItems = [];
		toolbarItems.push('undo');
		toolbarItems.push('redo');
		toolbarItems.push('|');
		toolbarItems.push('bold');
		toolbarItems.push('italic');
		toolbarItems.push('|');
		toolbarItems.push('fontColor');
		toolbarItems.push('fontBackgroundColor');
		toolbarItems.push('|');
		toolbarItems.push('bulletedList');
		toolbarItems.push('numberedList');
		toolbarItems.push('blockQuote');
		toolbarItems.push('alignment');
		toolbarItems.push('|');
		toolbarItems.push('insertTable');
		@if ($allowsLinks)
			toolbarItems.push('link');
		@endif
		toolbarItems.push('|');
		toolbarItems.push('heading');
		toolbarItems.push('|');
		toolbarItems.push('indent');
		toolbarItems.push('outdent');
		toolbarItems.push('|');
		toolbarItems.push('removeFormat');
		
		onDocumentReady((event) => {
			{{-- Options --}}
			const ckeditorOptions = {
				language: '{{ $foundLocale }}',
				toolbar: {
					items: toolbarItems
				},
				table: {
					contentToolbar: [
						'tableColumn',
						'tableRow',
						'mergeTableCells'
					]
				}
			};
			
			{{-- Init. --}}
			const ckeditorEl = document.querySelector(ckeditorSelector);
			if (ckeditorEl) {
				ClassicEditor.create(ckeditorEl, ckeditorOptions)
				.then(editor => {
					window.editor = editor;
				}).catch(error => {
					console.error('Oops, something gone wrong!');
					console.error(error);
				});
			}
		});
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_styles")
@endpush

@push("{$viewName}_helper_scripts")
@endpush
