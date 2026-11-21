{{-- adniro: photos (single-step mode; multi-step has its own photos step) --}}
@if (isset($picturesLimit) && is_numeric($picturesLimit) && $picturesLimit > 0)
	{{-- pictures --}}
	@php
		$picturesRequired = (config('settings.listing_form.picture_mandatory') == '1');
		$formPictures = $listingForm->isEdit ? data_get($post ?? [], 'pictures') : ($pictures ?? []);

		$savedPictures = collect($formPictures)
			->map(function ($item) {
				return [
					'key'  => $item['id'] ?? null,
					'path' => $item['file_path'] ?? null,
					'url'  => $item['url']['medium'] ?? null,
				];
			})->toArray();

		$deleteUrlPattern = ($listingForm->isEdit && !empty($listingForm->postId))
			? url('posts/' . $listingForm->postId . '/photos/{id}/delete')
			: null;

		$picturesHint = t('add_up_to_x_pictures_text', ['pictures_number' => $picturesLimit]);
		$picturesHint .= ' ' . t('file_types', ['file_types' => getAllowedFileFormatsHint('image')]);

		$fileinputOptions = [
			'previewFileType'   => 'image',
			'showPreview'       => 'true',
			'dropZoneEnabled'   => 'true',
			'browseOnZoneClick' => 'true',
			'showCaption'       => 'false',
		];
		if (!$listingForm->isEdit) {
			$fileinputOptions['uploadUrl'] = '/';
		}
	@endphp
	@if (config('settings.listing_form.one_picture_field_for_multiple_selections') == '1')
		@include('helpers.forms.fields.fileinput', [
			'label'            => t('pictures'),
			'name'             => 'pictures',
			'required'         => $picturesRequired,
			'attributes'       => ['accept' => 'image/*'],
			'value'            => $savedPictures,
			'hint'             => $picturesHint,
			'allowsMultiple'   => true,
			'limit'            => $picturesLimit,
			'pluginOptions'    => $fileinputOptions,
			'deleteUrlPattern' => $deleteUrlPattern,
		])
	@else
		@include('helpers.forms.fields.fileinput-multiple', [
			'label'         => t('pictures'),
			'name'          => 'pictures',
			'placeholder'   => t('Picture X', ['number' => '{index}']),
			'required'      => $picturesRequired,
			'attributes'    => ['accept' => 'image/*'],
			'value'         => $savedPictures,
			'hint'          => $picturesHint,
			'limit'         => $picturesLimit,
			'pluginOptions' => [
				'previewFileType' => 'image',
				'showPreview'     => 'true',
			],
		])
	@endif
@endif
