{{--
 * adniro:ai - the "AI help" menu beside a field's label (redesigned listing form, writing help on).
 *   field      title|description
 *   limitText  "28 of 30 AI writing helps left today", or null
 * Used by listing-form/ai-field-tools.js.
--}}
@php
	$labels = [
		'suggest' => trans('listing_form.' . ($field === 'title' ? 'ai_suggest_title' : 'ai_suggest_description')),
		'shorten' => trans('listing_form.ai_shorten'),
		'clearer' => trans('listing_form.ai_clearer'),
	];
@endphp
<span class="dropdown lf-ai-tools">
	<button type="button" class="btn btn-sm lf-ai-tools-toggle dropdown-toggle" id="lfAiTools-{{ $field }}"
	        data-bs-toggle="dropdown" aria-expanded="false">
		<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> {{ trans('listing_form.ai_help') }}
	</button>
	<ul class="dropdown-menu dropdown-menu-end lf-ai-tools-menu" aria-labelledby="lfAiTools-{{ $field }}">
		@foreach ($labels as $action => $label)
			<li>
				<button type="button" class="dropdown-item" data-lf-write="{{ $action }}" data-lf-field="{{ $field }}">{{ $label }}</button>
			</li>
		@endforeach
		@if (!empty($limitText))
			<li><hr class="dropdown-divider"></li>
			<li><span class="dropdown-item-text lf-ai-tools-limit" data-lf-write-limit>{{ $limitText }}</span></li>
		@endif
	</ul>
</span>
