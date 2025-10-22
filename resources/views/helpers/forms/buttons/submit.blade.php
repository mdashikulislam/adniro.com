@php
	use App\Helpers\Common\Arr;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$type = 'button'; // button, submit, reset
	$buttonLabel ??= 'Submit';
	$buttonType ??= 'primary'; // Base type: primary, secondary, success, etc.
	$outline ??= false; // Toggle outline style (btn-outline-* vs btn-*)
	$class ??= null; // Additional classes to append
	$id ??= null;
	$disabled ??= false;
	
	$type = in_array($type, ['button', 'submit', 'reset']) ? $type : 'submit';
	
	$baseClass = $outline ? 'btn-outline-' . $buttonType : 'btn-' . $buttonType;
	$fullClass = 'btn ' . $baseClass . ($class ? ' ' . $class : '');
	
	$attr = [];
	if (!empty($id)) {
		$attr['id'] = $id;
	}
	if ($disabled) {
		$attr['disabled'] = true;
	}
	$attrStr = Arr::toAttributes($attr);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<button type="{{ $type }}" class="{{ $fullClass }}"{!! $attrStr !!}>
				{!! $buttonLabel !!}
			</button>
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
