@php
	$customFields ??= [];
@endphp
@if (!empty($customFields))
	<section class="listing-section">
		<h2 class="listing-section-title">
			<span class="listing-section-icon"><i class="bi bi-list-check"></i></span>
			{{ t('Additional Details') }}
		</h2>
		<div class="listing-specs">
			@foreach($customFields as $field)
				@php
					$fieldType = data_get($field, 'type');
					$fieldName = data_get($field, 'name');
					$fieldValue = data_get($field, 'value');
				@endphp
				@if (is_array($fieldValue))
					@if (count($fieldValue) > 0)
						<div class="listing-spec listing-spec-wide">
							<span class="listing-spec-label">{{ $fieldName }}</span>
							<ul class="listing-spec-list list-unstyled">
								@foreach($fieldValue as $valueItem)
									<li><i class="bi bi-check2-circle"></i> {{ $valueItem }}</li>
								@endforeach
							</ul>
						</div>
					@endif
				@else
					@if (is_string($fieldValue) || is_numeric($fieldValue) || is_bool($fieldValue))
						@if ($fieldType == 'file')
							<div class="listing-spec listing-spec-wide listing-spec-file">
								<span class="listing-spec-label">{{ $fieldName }}</span>
								<a class="btn btn-outline-primary btn-sm listing-spec-download" href="{{ $fieldValue }}" target="_blank">
									<i class="bi bi-paperclip"></i> {{ t('Download') }}
								</a>
							</div>
						@elseif ($fieldType == 'video')
							<div class="listing-spec listing-spec-wide">
								<span class="listing-spec-label">{{ $fieldName }}</span>
								<div class="listing-spec-video ratio ratio-16x9">
									{!! $fieldValue !!}
								</div>
							</div>
						@else
							<div class="listing-spec">
								<span class="listing-spec-label">{{ $fieldName }}</span>
								<span class="listing-spec-value">{!! $fieldValue !!}</span>
							</div>
						@endif
					@endif
				@endif
			@endforeach
		</div>
	</section>
@endif
