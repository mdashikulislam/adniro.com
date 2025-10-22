@extends('admin.layouts.master')

@php
	use App\Helpers\Common\JsonUtils;
	
	$presets ??= [];
@endphp

@section('header')
	<div class="row page-titles">
		<div class="col-md-6 col-12 align-self-center">
			<h3 class="mb-0 fw-bold">
				{{ trans('admin.homepage_presets') }}
			</h3>
		</div>
		<div class="col-md-6 col-12 align-self-center d-none d-md-flex justify-content-end">
			<ol class="breadcrumb mb-0 p-0 bg-transparent">
				<li class="breadcrumb-item"><a href="{{ urlGen()->adminUrl() }}">{{ trans('admin.dashboard') }}</a></li>
				<li class="breadcrumb-item active d-flex align-items-center">{{ trans('admin.homepage_presets') }}</li>
			</ol>
		</div>
	</div>
@endsection

@section('content')
	{{-- Default box --}}
	<div class="row">
		<div class="col-12">
			
			<div class="card border-0">
				<div class="card-body border-top">
					
					<div class="container py-5">
						<div class="text-center mb-5">
							<h1 class="mb-3">{{ trans('admin.preset_selection_title') }}</h1>
							<p class="text-muted">
								{!! trans('admin.preset_selection_description', ['sectionUrl' => urlGen()->adminUrl('homepage/sections')]) !!}
							</p>
						</div>
						
						<div class="row g-4" id="pageTypeGrid">
							@if (!empty($presets))
								@foreach($presets as $key => $preset)
									@php
										$imageUrl = $preset['image'] ?? '';
										$name = $preset['name'] ?? '';
										$description = $preset['description'] ?? '';
										$inputPreset = (array)($preset['preset'] ?? []);
										$inputPreset = JsonUtils::ensureJson($inputPreset);
									@endphp
									<div class="col-md-6 col-lg-4">
										<div class="page-type-card border border-3 border-white rounded overflow-hidden bg-white"
										     data-preset-index="{{ $key }}"
										     data-preset="{{ $inputPreset }}"
										>
											<img src="{{ $imageUrl }}"
											     alt="{{ $name }}"
											     class="page-type-image w-100 border rounded"
											     data-bs-toggle="tooltip"
											     title="{{ $description }}"
											>
											<div class="p-3">
												<h3 class="fw-semibold fs-5 mb-2 text-center">{{ $name }}</h3>
												{{--<p class="text-muted small mb-0">{{ $description }}</p>--}}
											</div>
										</div>
									</div>
								@endforeach
							@endif
						</div>
						
						<div class="text-center mt-5">
							<button id="submitBtn" class="btn btn-primary btn-lg px-5" disabled>
								{{ trans('admin.apply_preset') }}
							</button>
						</div>
					</div>
					
				</div>
			</div>
			
		</div>
	</div>
@endsection

@section('after_styles')
	{{-- Ladda Buttons (loading buttons) --}}
	<link href="{{ asset('assets/plugins/ladda/ladda-themeless.min.css') }}" rel="stylesheet" type="text/css" />
	
	<style>
		.page-type-card {
			cursor: pointer;
			transition: all 0.3s ease;
		}
		
		.page-type-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 4px 15px rgba(0,0,0,0.1);
		}
		
		.page-type-image {
			/*height: 200px;*/
			object-fit: cover;
		}
	</style>
@endsection
@section('after_scripts')
	<script>
		onDocumentReady((event) => {
			let selectedPresetIndex = null;
			let selectedPreset = null;
			const cards = document.querySelectorAll('.page-type-card');
			const submitBtn = document.getElementById('submitBtn');
			
			cards.forEach(card => {
				card.addEventListener('click', function() {
					/* Remove selected class from all cards */
					cards.forEach(c => {
						c.classList.remove('border-primary', 'shadow-lg');
						c.classList.add('border-white');
						
						const cardImage = c.querySelector('img');
						if (cardImage) {
							cardImage.classList.remove('border-white');
						}
					});
					
					/* Add selected class to clicked card */
					this.classList.remove('border-white');
					this.classList.add('border-primary', 'shadow-lg');
					
					const cardImage = this.querySelector('img');
					if (cardImage) {
						cardImage.classList.add('border-white');
					}
					
					/* Store selected preset data */
					selectedPresetIndex = this.getAttribute('data-preset-index');
					selectedPreset = this.getAttribute('data-preset');
					
					/* Enable submit button */
					submitBtn.disabled = false;
				});
			});
			
			submitBtn.addEventListener('click', function(event) {
				event.preventDefault(); /* Prevents submission or reloading */
				
				/* Apply the preset */
				if (selectedPresetIndex && selectedPreset) {
					applyPreset(selectedPresetIndex, selectedPreset);
				}
			});
		});
		
		/**
		 * Apply a preset
		 *
		 * @param {int} index
		 * @param {object} preset
		 * @returns {Promise<boolean>}
		 */
		async function applyPreset(index, preset) {
			const presetUrl = '{{ urlGen()->adminUrl('homepage/presets') }}';
			const url = `${presetUrl}/${index}`;
			
			/* Build input data */
			const _tokenEl = document.querySelector('input[name=_token]');
			const data = {
				'_token': _tokenEl.value ?? null,
				'preset': preset
			};
			
			showWaitingDialog();
			
			try {
				const json = await httpRequest('POST', url, data);
				/* console.log(json); */
				
				hideWaitingDialog();
				
				if (json.success === true) {
					pnotifyAlertClient('success', json.message);
				} else {
					pnotifyAlertClient('error', json.message);
				}
				
				return false;
			} catch (error) {
				hideWaitingDialog();
				
				let message = getErrorMessage(error);
				if (message !== null) {
					pnotifyAlertClient('error', message);
				}
				
				return false;
			}
		}
	</script>
@endsection
