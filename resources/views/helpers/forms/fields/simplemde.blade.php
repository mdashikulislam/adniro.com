{{-- simplemde (Markdown Editor) --}}
{{-- https://simplemde.com --}}
{{-- https://github.com/sparksuite/simplemde-markdown-editor --}}
@php
	use App\Helpers\Common\JsonUtils;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'simplemde';
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
	$pluginAttributes = $pluginOptions['attributes'] ?? [];
	$pluginAttributesRaw = $pluginOptions['attributesRaw'] ?? '';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	// attributes ================================================================================
	$attrs = [];
	if (!empty($pluginAttributes)) {
		foreach($pluginAttributes as $index => $item) {
			$item = is_bool($item) ? ($item ? 'true' : 'false') : $item;
			$attrs[$index] = $item;
		}
	}
	
	$attrsRaw = [];
	if (!empty($pluginAttributesRaw)) {
		$attrsRaw = JsonUtils::isJson($pluginAttributesRaw) ? json_decode($pluginAttributesRaw, true) : [];
	}
	
	$attrs = array_merge($attrs, $attrsRaw);
	$jsonAttrs = collect($attrs)->toJson();
	// ===========================================================================================
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'simplemde');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<textarea
					id="simplemde_{{ $id }}"
					name="{{ $name }}"
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
	<link href="{{ asset('assets/plugins/simplemde/1.11.2/simplemde.min.css') }}" rel="stylesheet">
	<style>
		.CodeMirror-fullscreen, .editor-toolbar.fullscreen {
			z-index: 9999 !important;
		}
		.CodeMirror {
			min-height: auto !important;
		}
		.CodeMirror, .CodeMirror-scroll {
			min-height: {{ (int)$height }}px !important;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset('assets/plugins/simplemde/1.11.2/simplemde.min.js') }}"></script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		var simplemdeSelector = 'textarea[name="{{ $name }}"].simplemde';
		var simplemdeMinHeight = '{{ $height }}px';
		
		{{-- Toolbar --}}
		var simplemdeToolbar = [];
		simplemdeToolbar.push('bold');
		simplemdeToolbar.push('italic');
		simplemdeToolbar.push('strikethrough');
		simplemdeToolbar.push('|');
		simplemdeToolbar.push('heading');
		simplemdeToolbar.push('heading-smaller');
		simplemdeToolbar.push('heading-bigger');
		simplemdeToolbar.push('heading-1');
		simplemdeToolbar.push('heading-2');
		simplemdeToolbar.push('heading-3');
		simplemdeToolbar.push('|');
		simplemdeToolbar.push('unordered-list');
		simplemdeToolbar.push('ordered-list');
		simplemdeToolbar.push('clean-block');
		@if ($allowsLinks)
			simplemdeToolbar.push('|');
			simplemdeToolbar.push('link');
			simplemdeToolbar.push('image');
		@endif
		simplemdeToolbar.push('|');
		simplemdeToolbar.push('table');
		simplemdeToolbar.push('quote');
		@if (isFromAdminPanel())
			simplemdeToolbar.push('|');
			simplemdeToolbar.push('fullscreen');
			simplemdeToolbar.push('preview');
			simplemdeToolbar.push('code');
		@endif
		simplemdeToolbar.push('guide');
		
		onDocumentReady((event) => {
			const simplemdeEl = document.querySelector(simplemdeSelector);
			const placeholder = '{{ $placeholder }}';
			
			if (simplemdeEl) {
				{{-- Options --}}
				const simplemdeOptions = {
					element: simplemdeEl,
					placeholder: placeholder,
					toolbar: simplemdeToolbar,
					toolbarTips: false
				};
				
				{{-- Init. --}}
				const simplemde = new SimpleMDE(simplemdeOptions);
				
				simplemde.options.minHeight = simplemde.options.minHeight || simplemdeMinHeight;
				simplemde.codemirror.getScrollerElement().style.minHeight = simplemde.options.minHeight;
				
				$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
					setTimeout(() => simplemde.codemirror.refresh(), 10);
				});
			}
		});
	</script>
@endpush
