@php
	use App\Helpers\Common\Flash\FlashMessage;
	use App\Helpers\Common\JsonUtils;
	
	// Retrieve flash messages
	$flashMessages = FlashMessage::all();
	
	// Retrieve immediate flash messages (Without session store)
	if (isset($immediateMessages)) {
		$immediateMessages = is_array($immediateMessages) ? $immediateMessages : [];
        $flashMessages = array_merge($flashMessages, $immediateMessages);
    }
@endphp
@if(!empty($flashMessages))
	@php
		$globalOptions = (array)($presenterOptions ?? []);
		
		$fade = $globalOptions['fade'] ?? false;
		$fadeClass = $fade ? ' fade' : '';
		
		$supportedSizes = ['sm', 'lg', 'xl'];
		$size = $globalOptions['size'] ?? '';
		$sizeClass = (!empty($size) && in_array($size, $supportedSizes)) ? " modal-$size" : '';
		
		$scrollable = $globalOptions['scrollable'] ?? false;
		$scrollableClass = $scrollable ? ' modal-dialog-scrollable' : '';
		
		$showFooter = $globalOptions['showFooter'] ?? false;
		
		$autohide = $globalOptions['autohide'] ?? false;
		$delay = (int)($globalOptions['delay'] ?? 10000);
		$delayInSeconds = $delay / 1000;
		$showProgressBar = $globalOptions['showProgressBar'] ?? false;
		
		// Get the component options & convert them in JSON
		$options = (array)($globalOptions['options'] ?? []);
		$options = JsonUtils::ensureJson($options);
	@endphp
	@foreach($flashMessages as $flash)
		@php
			$id = $flash['id'];
			$level = $flash['level'] ?? '';
			$bsClass = $flash['bsClass'] ?? 'light';
			$icon = $flash['icon'] ?? '';
			$title = $flash['title'] ?? '';
			$message = $flash['message'] ?? '';
			$timestamp = $flash['timestamp'] ?? '';
			
			$hasTitle = !empty($title);
			$labelledby = "{$id}Label";
			// $showFooter = $hasTitle ? $showFooter : true;
		@endphp
		<div id="{{ $id }}" class="modal flash-modal{{ $fadeClass }}" tabindex="-1" aria-labelledby="{{ $labelledby }}" aria-hidden="true">
			<div class="modal-dialog{{ $sizeClass . $scrollableClass }}">
				<div class="modal-content border-0">
					<div class="modal-header border-bottom-0">
						@if ($hasTitle)
							<h4 class="modal-title fs-5 fw-bold" id="{{ $labelledby }}">{{ $title }}</h4>
						@endif
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
					</div>
					
					<div class="modal-body">
						<div class="alert alert-{{ $bsClass }}">
							{!! $message !!}
						</div>
						
						@if ($autohide && $showProgressBar)
							@php
								$countdownId = $id . '-countdown';
								$formattedDelay = '<span id="' . $countdownId . '" class="fw-bold">' . $delayInSeconds . '</span>';
							@endphp
							<p class="mt-3">
								{!! t('auto_close_in', ['delayInSeconds' => $formattedDelay]) !!}
							</p>
							<div class="progress">
								<div class="progress-bar" id="{{ $id }}-progressBar" style="width: 100%"></div>
							</div>
						@endif
					</div>
					
					@if ($showFooter)
						<div class="modal-footer d-flex justify-content-center">
							<button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ t('dismiss') }}</button>
						</div>
					@endif
				</div>
			</div>
		</div>
	@endforeach
	
	@pushonce('after_scripts_stack')
		<script>
			onDocumentReady((event) => {
				const options = {!! $options !!};
				const autohide = {{ $autohide ? 'true' : 'false' }};
				let delay = {{ $delay }};
				
				/* Initialize and show all modals */
				const modalElements = document.querySelectorAll('.flash-modal');
				
				if (modalElements.length > 0) {
					let currentModalIndex = 0;
					
					function showNextModal() {
						if (currentModalIndex >= modalElements.length) {
							return; /* All modals have been shown */
						}
						
						const modalElement = modalElements[currentModalIndex];
						const modal = new bootstrap.Modal(modalElement, options);
						
						/* Show the current modal */
						modal.show();
						
						if (autohide === true) {
							const delayInSeconds = delay / 1000;
							let timeLeft = delayInSeconds;
							const countdownElement = document.getElementById(modalElement.id + '-countdown');
							const progressBar = document.getElementById(modalElement.id + '-progressBar');
							let countdown;
							let isPaused = false;
							
							/* Reset progress bar to 100% for this modal */
							if (progressBar) {
								progressBar.style.width = '100%';
							}
							
							function startCountdown() {
								countdown = setInterval(() => {
									if (!isPaused) {
										timeLeft--;
										
										if (countdownElement) {
											countdownElement.textContent = timeLeft;
										}
										if (progressBar) {
											progressBar.style.width = (timeLeft / delayInSeconds * 100) + '%';
										}
										
										if (timeLeft <= 0) {
											clearInterval(countdown);
											modal.hide();
										}
									}
								}, 1000);
							}
							
							/* Add hover event listeners to pause/resume countdown on modal-content */
							const modalContent = modalElement.querySelector('.modal-content');
							modalContent.addEventListener('mouseenter', () => {
								isPaused = true;
							});
							
							modalContent.addEventListener('mouseleave', () => {
								isPaused = false;
							});
							
							/* Start the countdown */
							startCountdown();
						}
						
						/* Auto-remove from DOM after hiding and show next modal */
						modalElement.addEventListener('hidden.bs.modal', function () {
							this.remove();
							currentModalIndex++;
							showNextModal(); /* Show the next modal after this one is hidden */
						});
					}
					
					/* Start with the first modal */
					showNextModal();
				}
			});
		</script>
	@endpushonce
@endif
