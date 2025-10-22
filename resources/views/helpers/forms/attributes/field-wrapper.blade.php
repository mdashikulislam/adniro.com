@php
	use App\Helpers\Common\Arr;
	use App\Helpers\Common\Html\HtmlAttr;
	use App\Helpers\Common\Html\WrapperClassDefaults;
	
	/*
	 * Form Layout Rules
	 * -----------------
	 * default: fields wrappers need to "col-md-12" as class and "row" for the form tag
	 * horizontal: fields wrappers need to "row" as class, since label and the field div have "col-*" class
	 */
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= []; // Wrapper attributes (including "class")
	
	$baseClass ??= [];
	$wrapperBaseClass = $baseClass['wrapper'] ?? WrapperClassDefaults::for($layout);
	
	$viewName ??= null;
	$type ??= 'text';
	$name ??= '';
	$required ??= false;
	
	// Convert field name to dot separated name
	$dotSepName = arrayFieldToDotNotation($name);
	
	// Get attributes to print
	$attr = [];
	if (!empty($wrapper) && is_array($wrapper)) {
		// Wrapper attributes option is defined
		foreach ($wrapper as $attribute => $value) {
			if (!is_string($attribute)) continue;
			
			if ($attribute == 'class') {
				if (!empty($type)) {
					$class = [];
					$class[] = $value;
					if ($type == 'image') {
						$class[] = 'image';
					}
					if ($type == 'color_picker') {
						$class[] = 'coloris square';
					}
					if (!empty($class)) {
						$attr[$attribute] = implode(' ', $class);
					}
				} else {
					$attr[$attribute] = $value;
				}
			} else {
				$attr[$attribute] = $value;
			}
		}
		
		// "class" attribute is not set in wrapper attributes
		if (empty($wrapper['class'])) {
			// Add the class attribute (with some default values) related to the 'type' of field
			$class = [];
			if (!empty($type)) {
				if ($type == 'image') {
					$class[] = 'image';
				}
				if ($type == 'color_picker') {
					$class[] = 'coloris square';
				}
			}
			if (!empty($class)) {
				$attr['class'] = implode(' ', $class);
			}
		}
	} else {
		// Wrapper attributes option is not defined
		// Add the class attribute (with some default values) related to the 'type' of field
		$class = [];
		if (!empty($type)) {
			if ($type == 'image') {
				$class[] = 'image';
			}
			if ($type == 'color_picker') {
				$class[] = 'coloris square';
			}
		}
		if (!empty($class)) {
			$attr['class'] = implode(' ', $class);
		}
	}
	
	$attr = HtmlAttr::prepend($attr, 'class', $wrapperBaseClass);
	if (!empty($attr['class'])) {
	    // $attr = HtmlAttr::append($attr, 'class', $attr['class']);
	}
	
	$attrStr = Arr::toAttributes($attr);
@endphp
{!! $attrStr !!}
