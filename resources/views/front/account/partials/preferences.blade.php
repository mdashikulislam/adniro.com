@php
	use App\Helpers\Common\Date\TimeZoneManager;
	
	$authUserIsAdmin ??= false;
@endphp
<div class="col-12">
	<div class="card">
		<div class="card-header">
			<h5 class="card-title mb-0">
				{{ t('Settings') }}
			</h5>
		</div>
		<div class="card-body">
			<div class="row d-flex justify-content-center">
				<div class="col-xl-7 col-lg-8 col-md-10 col-sm-12">
					<form name="settings"
					      action="{{ urlGen()->accountPreferences() }}"
					      method="POST"
					      enctype="multipart/form-data"
					      role="form"
					>
						@csrf
						@method('PUT')
						
						<input name="panel" type="hidden" value="settings">
						<input name="user_id" type="hidden" value="{{ $authUser->getAuthIdentifier() }}">
						
						<div class="row">
							@if (config('settings.listing_page.activation_facebook_comments') && config('services.facebook.client_id'))
								{{-- disable_comments --}}
								@include('helpers.forms.fields.checkbox', [
									'label'    => t('disable_comments_on_listings'),
									'id'       => 'disableComments',
									'name'     => 'disable_comments',
									'required' => false,
									'value'    => $authUser->disable_comments ?? null,
								])
							@endif
							
							@if ($authUser->accept_terms != 1)
								{{-- accept_terms --}}
								@include('helpers.forms.fields.checkbox', [
									'label'    => t('accept_terms_label', ['attributes' => getUrlPageByType('terms')]),
									'id'       => 'acceptTerms',
									'name'     => 'accept_terms',
									'required' => true,
									'value'    => $authUser->accept_terms ?? null,
								])
								
								<input type="hidden" name="user_accept_terms" value="{{ (int)($authUser->accept_terms ?? 0) }}">
							@endif
							
							{{-- accept_marketing_offers --}}
							@include('helpers.forms.fields.checkbox', [
								'label'    => t('accept_marketing_offers_label'),
								'id'       => 'acceptMarketingOffers',
								'name'     => 'accept_marketing_offers',
								'required' => false,
								'value'    => $authUser->accept_marketing_offers ?? null,
							])
							
							{{-- time_zone --}}
							@php
								$tzOptions = TimeZoneManager::getTimeZones();
								$tzOptions = collect($tzOptions)
									->map(fn($item, $key) => ['value' => $key, 'text'  => $item])
									->toArray();
								
								$tzHint = $authUserIsAdmin
									? t('admin_preferred_time_zone_info', [
										'frontTz' => config('country.time_zone'),
										'country' => config('country.name'),
										'adminTz' => config('app.timezone'),
									])
									: t('preferred_time_zone_info', [
										'frontTz' => config('country.time_zone'),
										'country' => config('country.name'),
									]);
							@endphp
							@include('helpers.forms.fields.select2', [
								'label'       => t('preferred_time_zone_label'),
								'name'        => 'time_zone',
								'required'    => false,
								'placeholder' => t('select_a_time_zone'),
								'options'     => $tzOptions,
								'value'       => $authUser->time_zone ?? null,
								'hint'        => $tzHint,
							])
							
							{{-- button --}}
							<div class="col-12 mb-3 mt-3">
								<div class="row">
									<div class="col-md-12">
										<button type="submit" class="btn btn-primary">{{ t('Update') }}</button>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
