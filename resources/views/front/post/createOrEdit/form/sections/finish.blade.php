{{-- adniro: packages (single-step), captcha and terms (new listings) --}}
@includeWhen($isSingleStep, 'front.post.createOrEdit.singleStep.partials.packages')

@if (!$listingForm->isEdit)
	{{-- captcha --}}
	@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])

	@if (!auth()->check())
		{{-- accept_terms --}}
		@include('helpers.forms.fields.checkbox', [
			'label'     => t('accept_terms_label', ['attributes' => getUrlPageByType('terms')]),
			'id'        => 'acceptTerms',
			'name'      => 'accept_terms',
			'required'  => true,
			'value'     => $listingForm->acceptTerms,
			'baseClass' => ['wrapper' => 'mb-1 col-md-12'],
		])

		{{-- accept_marketing_offers --}}
		@include('helpers.forms.fields.checkbox', [
			'label'    => t('accept_marketing_offers_label'),
			'id'       => 'acceptMarketingOffers',
			'name'     => 'accept_marketing_offers',
			'required' => false,
			'value'    => $listingForm->acceptMarketingOffers,
		])
	@endif
@endif
