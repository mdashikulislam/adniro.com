@php
	// Get a WYSIWYG editor
	$defaultEditor = 'textarea';
	$editorFromConfig = config('settings.listing_form.wysiwyg_editor', $defaultEditor);
	$editorFromConfig = !empty($editorFromConfig) ? $editorFromConfig : $defaultEditor;
	$editor = $params['editor'] ?? $editorFromConfig;
	
	// Get the editor view
	$viewPath = 'helpers.forms.fields.';
	$defaultEditorView = $viewPath . $defaultEditor;
	$editorView = $viewPath . $editor;
	$editorView = view()->exists($editorView) ? $editorView : $defaultEditorView;
	
	// Get all variables available in the current view, then
	// Filter out Laravel's internal variables if needed
	$allVars = get_defined_vars();
	$bladeInternalVars = ['__data', '__path', '__env', 'app', 'errors'];
	$passedParams = array_diff_key($allVars, array_flip($bladeInternalVars));
	
	// Retrieve the right format for value
	$value = $passedParams['value'] ?? '';
	$value = !isWysiwygEnabled() ? strip_tags($value) : $value;
	$passedParams['value'] = $value;
@endphp
@include($editorView, $passedParams)
