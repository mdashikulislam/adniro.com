{{-- adniro: the hidden inputs each kind of listing form sends --}}
@csrf
@if ($listingForm->isEdit)
	@method('PUT')
	<input type="hidden" name="post_id" value="{{ $listingForm->postId }}">
	@if ($isSingleStep)
		<input type="hidden" name="payable_id" value="{{ $listingForm->postId }}">
	@endif
@else
	@honeypot
@endif
