{{-- tinymce (WYSIWYG Editor) --}}
{{-- https://www.tiny.cloud --}}
{{-- https://www.tiny.cloud/docs/tinymce/latest/basic-example/ --}}
{{-- https://github.com/tinymce/tinymce --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'tinymce';
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
	$height ??= 400;
	$pluginOptions ??= [];
	
	$language = $pluginOptions['language'] ?? app()->getLocale();
	$directionality = $pluginOptions['directionality'] ?? ((config('lang.direction') == 'rtl') ?  'rtl' : 'ltr');
	$menubar = $pluginOptions['menubar'] ?? 'false';
	$statusbar = $pluginOptions['statusbar'] ?? 'false';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'tinymce');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<textarea
					id="tinymce_{{ $id }}"
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
@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset('assets/plugins/tinymce/tinymce.min.js') }}"></script>
	@php
		$editorI18n = trans('tinymce', [], $language);
		$editorI18nJson = '';
		if (!empty($editorI18n)) {
			$editorI18nJson = collect($editorI18n)->toJson();
			$editorI18nJson = convertUTF8HtmlToAnsi($editorI18nJson);
		}
	@endphp
	<script>
		{{-- var tinymceSelector = 'textarea[name="{{ $name }}"].tinymce'; --}}
		var tinymceSelector = 'textarea.tinymce';
		var tinymcePlugins = '{{ $allowsLinks ? 'lists link table code' : 'lists table code' }}';
		var tinymceLanguage = '{{ !empty($editorI18nJson) ? $language : 'en' }}';
		var editorI18nJson = {!! !empty($editorI18nJson) ? $editorI18nJson : 'null' !!};
		
		{{-- Toolbar --}}
		var tinymceToolbar = '';
		tinymceToolbar += 'undo redo';
		tinymceToolbar += ' | ';
		tinymceToolbar += 'bold italic underline';
		tinymceToolbar += ' | ';
		tinymceToolbar += 'forecolor backcolor';
		tinymceToolbar += ' | ';
		tinymceToolbar += 'bullist numlist blockquote table';
		@if ($allowsLinks)
			tinymceToolbar += ' | ';
			tinymceToolbar += 'link unlink';
		@endif
		tinymceToolbar += ' | ';
		tinymceToolbar += 'alignleft aligncenter alignright';
		tinymceToolbar += ' | ';
		tinymceToolbar += 'outdent indent';
		tinymceToolbar += ' | ';
		tinymceToolbar += 'fontsizeselect';
		@if (isFromAdminPanel())
			tinymceToolbar += ' | ';
			tinymceToolbar += 'code';
		@endif
		
		{{-- Options --}}
		const tinymceOptions = {
			selector: tinymceSelector,
			language: tinymceLanguage,
			directionality: '{{ $directionality }}',
			height: {{ (int)$height }},
			menubar: {{ $menubar }},
			statusbar: {{ $statusbar }},
			plugins: tinymcePlugins,
			toolbar: tinymceToolbar,
			setup: (editor) => {
				/* Indicate that the value of this field has changed */
				editor.on('change', (e) => {
					const targetEl = editor.targetElm || null;
					if (targetEl) {
						targetEl.dispatchEvent(new Event('input', {bubbles: true}));
					}
				});
			},
		};
		
		onDocumentReady((event) => {
			applyTinyMCE(tinymceOptions, tinymceLanguage, editorI18nJson)
			
			// Listen for system theme changes
			const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
			mediaQuery.addEventListener('change', () => {
				removeTinyMCE(tinymceSelector);
				applyTinyMCE(tinymceOptions, tinymceLanguage, editorI18nJson)
			});
		});
		
		/**
		 * Apply the TinyMCE to the textarea
		 *
		 * @param options
		 * @param language
		 * @param i18n
		 */
		function applyTinyMCE(options, language, i18n) {
			if (isDarkThemeEnabled()) {
				options.content_css = 'dark';
				options.skin = 'oxide-dark';
			} else {
				if (options.content_css) {
					delete options.content_css;
				}
				if (options.skin) {
					delete options.skin;
				}
			}
			
			{{-- Init. --}}
			tinymce.init(options);
			
			{{-- i18n --}}
			if (i18n) {
				tinymce.addI18n(language, i18n);
			}
		}
		
		/**
		 * Remove the TinyMCE from the textarea
		 *
		 * @param domSelector
		 */
		function removeTinyMCE(domSelector) {
			tinymce.activeEditor.destroy();
			/* tinymce.remove(domSelector); */
			
			const textAreaEl = document.querySelector(domSelector);
			if (textAreaEl) {
				if (textAreaEl.style.visibility === 'hidden') {
					textAreaEl.style.visibility = 'visible';
				}
			}
		}
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_styles")
@endpush

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
@endpush
