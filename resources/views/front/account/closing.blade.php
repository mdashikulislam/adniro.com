{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
--}}
@extends('front.layouts.master')

@php
	$authUserIsAdmin ??= true;
@endphp
@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				<div class="col-md-3">
					@include('front.account.partials.sidebar')
				</div>
				
				<div class="col-md-9">
					@include('front.account.partials.header', [
						'headerTitle' => '<i class="bi bi-person-x"></i> ' . t('close_account')
					])
					
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2">
						
						@if ($authUserIsAdmin)
							<div class="alert alert-danger mb-0" role="alert">
								{{ t('Admin users can not be deleted by this way') }}
							</div>
						@else
							<p>
								{{ t('are_you_sure_to_close_account') }}
							</p>
							
							<form action="{{ urlGen()->accountClosing() }}" method="POST" role="form">
								@csrf
								
								{{-- close_account_confirmation --}}
								@php
									$closingOptions = [
										['value' => '1', 'text' => t('Yes')],
										['value' => '0', 'text' => t('No')],
									];
								@endphp
								@include('helpers.forms.fields.radio', [
									'label'    => t('close_account'),
									'name'     => 'close_account_confirmation',
									'inline'   => true,
									'required' => true,
									'options'  => $closingOptions,
									'value'    => '0',
									'hint'     => t('your_data_will_permanently_deleted') . ' ' . t('action_warning')
								])
								
								{{-- button --}}
								<div class="row mb-3 mt-4">
									<div class="col-md-12">
										<button type="submit" class="btn btn-danger">{{ t('submit') }}</button>
									</div>
								</div>
							</form>
						@endif

					</div>
				</div>

			</div>
		</div>
	</div>
@endsection
