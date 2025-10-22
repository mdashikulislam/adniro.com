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

@section('wizard')
    @include('front.post.createOrEdit.multiSteps.partials.wizard')
@endsection

@php
	$picturesLimit ??= 0;
	$picturesLimit = is_numeric($picturesLimit) ? $picturesLimit : 0;
	$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : 1;
	
	// Get the listing pictures (by applying the picture limit)
	$pictures = $picturesInput ?? [];
	$pictures = collect($pictures)->slice(0, $picturesLimit)->all();
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
	$formActionUrl ??= request()->fullUrl();
	$nextStepUrl ??= '/';
	$nextStepLabel ??= t('submit');
@endphp
@section('content')
	@include('front.common.spacer')
    <div class="main-container">
        <div class="container">
            <div class="row">
    
                @include('front.post.partials.notification')
                
                <div class="col-md-12">
                    <div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-sm-3">
						
                        <h3 class="fw-bold border-bottom pb-3 mb-4">
							<i class="fa-solid fa-camera"></i> {{ t('Photos') }}
						</h3>
						
                        <div class="row">
                            <div class="col-md-12">
                                <form id="payableForm"
                                      action="{{ $formActionUrl }}"
                                      method="POST"
                                      enctype="multipart/form-data"
                                      onsubmit="actionButton.disabled = true; return true;"
                                >
	                                @csrf
	                                
                                    <div class="row">
                                        @if ($picturesLimit > 0)
											{{-- pictures --}}
	                                        @php
		                                        $picturesRequired = (config('settings.listing_form.picture_mandatory') == '1');
												
												$savedPictures = collect($pictures)->map(function ($filePath, $key) {
													// $url = thumbParam($filePath)->setOption('picture-md')->url();
													// $url = hasTemporaryPath($filePath) ? $disk->url($filePath) : $url;
													$url = thumbService($filePath)->resize('picture-md')->url();
													
													return [
														'key'  => $key,
														'path' => $filePath,
														'url'  => $url,
													];
												})->toArray();
												
												$uploadUrl = url('posts/create/photos');
												$uploadUrl = urlBuilder($uploadUrl)->setParameters(request()->only(['packageId']))->toString();
												$deleteUrlPattern = url('posts/create/photos/{id}/delete');
												$reorderUrl = url('posts/create/photos/reorder');
												
												$picturesHint = t('add_up_to_x_pictures_text', ['pictures_number' => $picturesLimit]);
												$picturesHint .= '<br>' . t('file_types', ['file_types' => getAllowedFileFormatsHint('image')]);
	                                        @endphp
		                                    @include('helpers.forms.fields.fileinput-ajax-multiple', [
												'name'       => 'pictures',
												'label'      => t('pictures'),
												'labelClass' => 'fw-bold',
												'required'   => $picturesRequired,
												'attributes' => ['accept' => 'image/*'],
												'value'      => $savedPictures,
												'hint'       => $picturesHint,
												'limit'      => $picturesLimit,
												'pluginOptions'    => [
													'uploadUrl' => $uploadUrl,
												],
												'reorderUrl'       => $reorderUrl,
												'deleteUrlPattern' => $deleteUrlPattern,
												'nextStepLabel'    => $nextStepLabel,
											])
                                        @endif
	                                       
                                        <div class="col-12">
	                                        <div id="uploadError" class="mt-2" style="display: none;"></div>
	                                        <div id="uploadSuccess" class="alert alert-success fade show mt-2" style="display: none;"></div>
                                        </div>
	
                                        {{-- button --}}
                                        <div class="col-12 mt-4">
	                                        <div class="row">
	                                            <div class="col-md-6 mb-md-0 mb-2 text-start d-grid">
													<a href="{{ $previousStepUrl }}" class="btn btn-secondary btn-lg">
														{!! $previousStepLabel !!}
													</a>
	                                            </div>
		                                        <div class="col-md-6 mb-md-0 mb-2 text-end d-grid">
													<button id="nextStepBtn" name="actionButton" class="btn btn-primary btn-lg">
														{!! $nextStepLabel !!}
													</button>
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
        </div>
    </div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
