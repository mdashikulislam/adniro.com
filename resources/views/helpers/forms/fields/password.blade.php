{{-- password --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= [];
	$viewName = 'password';
	$type = 'password';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$prefix ??= null;
	$suffix ??= null;
	$required ??= false;
	$hint ??= null;
	
	$wrapper = \App\Helpers\Common\Html\HtmlAttr::prepend($wrapper, 'class', 'password-field');
	
	$togglePassword ??= null; // 'link', 'icon' or null
	$inputGroupClass = null;
	if (!empty($togglePassword) && in_array($togglePassword, ['link', 'icon'])) {
		$pwdClass = 'text-muted text-decoration-none toggle-password-link';
		if ($togglePassword == 'link') {
			// labelRightContent
			$tooltip = 'data-bs-toggle="tooltip" data-bs-title="' . trans('auth.show_password') . '"';
			$labelRightContent = '<i class="fa-regular fa-eye-slash"></i> ';
			$labelRightContent .= '<a href="" class="' . $pwdClass . '" ' . $tooltip . ' data-toggle-text="true" data-ignore-guard="true">';
			$labelRightContent .= trans('auth.show');
			$labelRightContent .= '</a>';
		} else {
			$inputGroupClass = 'toggle-password-wrapper';
			$suffix = '<a href="" class="' . $pwdClass . '" data-ignore-guard="true"><i class="fa-regular fa-eye-slash"></i></a>';
		}
	}
	$inputGroupClass = !empty($inputGroupClass) ? ' ' . $inputGroupClass : '';
	
	$isAutoHintEnabled = (is_null($hint) || (is_bool($hint) && $hint));
	if ($isAutoHintEnabled) {
		$passwordTips = getPasswordTips();
		$passwordHint = collect($passwordTips)
			->map(fn ($item) => '<span class="d-block"><i class="bi bi-check2"></i> ' . $item . '</span>')
			->join("\n");
		$hint = $passwordHint;
	}
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);

	$hasInputGroup = (!empty($prefix) || !empty($suffix));
	
	// Handle error class for "input-group"
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	$isInvalidClass = !empty($isInvalidClass) ? ' ' . $isInvalidClass : '';
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@if (!empty($prefix) || !empty($suffix))
				<div class="input-group{{ $inputGroupClass . $isInvalidClass }}">
					@endif
					@if (!empty($prefix))
						<span class="input-group-text">{!! $prefix !!}</span>
					@endif
					<input
							type="password"
							id="{{ $id }}"
							name="{{ $name }}"
							@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
							autocomplete="new-password"
							@include('helpers.forms.attributes.field')
					>
					@if (!empty($suffix))
						<span class="input-group-text">{!! $suffix !!}</span>
					@endif
					@if (!empty($prefix) || !empty($suffix))
				</div>
			@endif
			
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
	<script src="{{ asset('assets/auth/js/toggle-password-visibility.js') }}"></script>
@endpushonce
