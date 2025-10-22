@php
	use App\Helpers\Common\Arr;
	use App\Helpers\Common\Html\FieldClassDefaults;
	use App\Helpers\Common\Html\HtmlAttr;
	use Illuminate\Support\ViewErrorBag;
	
	$viewName ??= null;
	$type ??= 'text';
	$name ??= '';
	$required ??= false;
	$attributes ??= [];
	
	$baseClass ??= [];
	$fieldBaseClass = $baseClass['field'] ?? FieldClassDefaults::for($type, $viewName);
	
	$dotSepName = arrayFieldToDotNotation($name);
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	// Get Attributes Output
	$attr = [];
	if (!empty($attributes)) {
		foreach ($attributes as $attribute => $attrValue) {
			if (!is_string($attribute)) continue;
			
			if (is_bool($attrValue)) {
				$attr[$attribute] = $attrValue;
				continue;
			}
			
			$arr = [];
			$arr[] = $attrValue;
			
			if ($attribute == 'class') {
				if (!empty($isInvalidClass)) {
					$arr[] = $isInvalidClass;
				}
			}
			
			$attr[$attribute] = implode(' ', $arr);
		}
		
		if (!isset($attributes['class'])) {
			$class = [];
			if (!empty($isInvalidClass)) {
				$class[] = $isInvalidClass;
			}
			$attr['class'] = implode(' ', $class);
		}
	} else {
		$class = [];
		if (!empty($isInvalidClass)) {
			$class[] = $isInvalidClass;
		}
		$attr['class'] = implode(' ', $class);
	}
	
	$attr = HtmlAttr::prepend($attr, 'class', $fieldBaseClass);
	if (!empty($attr['class'])) {
	    // $attr = HtmlAttr::append($attr, 'class', $attr['class']);
	}
	
	$attrStr = Arr::toAttributes($attr);
@endphp
{!! $attrStr !!}
