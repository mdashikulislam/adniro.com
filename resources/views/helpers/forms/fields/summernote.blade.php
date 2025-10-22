{{-- summernote (WYSIWYG Editor) --}}
{{-- https://summernote.org/examples/ --}}
{{-- https://github.com/summernote/summernote/ --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'summernote';
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
	
	$themeList = ['bs4', 'bs5', 'lite'];
	$theme ??= 'bs5';
	$theme = in_array($theme, $themeList) ? $theme : null;
	$allowsLinks ??= (
		isFromAdminPanel()
		|| (
			config('settings.listing_form.remove_url_before') != '1' &&
			config('settings.listing_form.remove_url_after') != '1'
		)
	);
	$height ??= 400;
	$pluginOptions ??= [];
	
	$lang = $pluginOptions['lang'] ?? app()->getLocale();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'summernote');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<textarea
					id="summernote_{{ $id }}"
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
	$pluginBasePath = 'assets/plugins/summernote/0.9.1/';
	$pluginFullPath = public_path($pluginBasePath);
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	@if (!empty($theme))
		<link href="{{ asset($pluginBasePath . "summernote-{$theme}.min.css") }}" rel="stylesheet">
	@else
		<link href="{{ asset($pluginBasePath . 'summernote.min.css') }}" rel="stylesheet">
	@endif
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	@if (!empty($theme))
		<script src="{{ asset($pluginBasePath . "summernote-{$theme}.min.js") }}"></script>
	@else
		<script src="{{ asset($pluginBasePath . 'summernote.min.js') }}"></script>
	@endif
	@php
		$editorLocale = '';
		if (file_exists($pluginFullPath . 'lang/summernote-' . getLangTag($lang) . '.js')) {
			$editorLocale = getLangTag($lang);
		}
		if (empty($editorLocale)) {
			if (file_exists($pluginFullPath . 'lang/summernote-' . strtolower($lang) . '.js')) {
				$editorLocale = strtolower($lang);
			}
		}
		if (empty($editorLocale)) {
			$editorLocale = 'en-US';
		}
	@endphp
	@if ($editorLocale != 'en-US')
		<script src="{{ url($pluginBasePath . 'lang/summernote-' . $editorLocale . '.js') }}" type="text/javascript"></script>
	@endif
	
	<script>
		onDocumentReady((event) => {
			const lang = '{{ $editorLocale }}';
			
			{{-- Toolbar --}}
			const summernoteToolbar = [];
			summernoteToolbar.push(['historic', ['undo', 'redo']]);
			summernoteToolbar.push(['style', ['bold', 'italic', 'underline', 'strikethrough', 'clear']]);
			summernoteToolbar.push(['color', ['forecolor', 'backcolor']]);
			summernoteToolbar.push(['para', ['ul', 'ol', 'paragraph']]);
			summernoteToolbar.push(['table', ['table']]);
			summernoteToolbar.push(['script', ['superscript', 'subscript']]);
			@if ($allowsLinks)
				summernoteToolbar.push(['insert', ['link']]);
			@endif
			@if (isFromAdminPanel())
				summernoteToolbar.push(['view', ['fullscreen', 'codeview']]);
			@endif
			
			{{-- Options --}}
			const summernoteOptions = {
				lang: lang,
				height: {{ (int)$height }},
				toolbar: summernoteToolbar
			};
			@if (!empty($placeholder))
				summernoteOptions.placeholder = '{{ $placeholder }}';
			@endif
			
			{{-- Init. --}}
			const summernoteEl = $('textarea.summernote');
			if (summernoteEl.length > 0) {
				summernoteEl.summernote(summernoteOptions);
			}
		});
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_styles")
@endpush

@push("{$viewName}_helper_scripts")
@endpush
