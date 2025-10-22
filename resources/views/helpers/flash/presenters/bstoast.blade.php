{{-- resources/views/components/flash-messages.blade.php --}}
@php
	use App\Helpers\Common\Flash\FlashMessage;
	use App\Helpers\Common\JsonUtils;
	use Illuminate\Support\Carbon;
	
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
		
		// Define supported placement list
		$supportedPositions = [
			'topLeft'      => 'top-0 start-0',
			'topCenter'    => 'top-0 start-50 translate-middle-x',
			'topRight'     => 'top-0 end-0',
			'middleLeft'   => 'top-50 start-0 translate-middle-y',
			'middleCenter' => 'top-50 start-50 translate-middle',
			'middleRight'  => 'top-50 end-0 translate-middle-y',
			'bottomLeft'   => 'bottom-0 start-0',
			'bottomCenter' => 'bottom-0 start-50 translate-middle-x',
			'bottomRight'  => 'bottom-0 end-0',
		];
		
		// Get 'placement' using position
		$defaultPosition = 'bottomRight';
		$position = $globalOptions['position'] ?? $defaultPosition;
		$position = array_key_exists($position, $supportedPositions) ? $position : $defaultPosition;
		$placement = $supportedPositions[$position];
		
		// Get the component options & convert them in JSON
		$options = (array)($globalOptions['options'] ?? []);
		$options = JsonUtils::ensureJson($options);
	@endphp
	{{-- Flash Messages Toast Container --}}
	<div class="toast-container position-fixed p-3 {{ $placement }}" style="z-index: 9999;">
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
			@endphp
			<div id="{{ $id }}"
			     class="toast flash-toast align-items-center text-bg-{{ $bsClass }} border-0 p-2 mb-2"
			     role="alert"
			     aria-live="assertive"
			     aria-atomic="true"
			>
				@if ($hasTitle)
					<div class="toast-header text-bg-{{ $bsClass }} pt-0 ps-1 pe-2">
						<i class="{{ $icon }} me-2"></i>
						<strong class="me-auto">{{ $title }}</strong>
						
						<small class="text-light">
							{{ Carbon::parse($timestamp)->diffForHumans() }}
						</small>
						
						<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="{{ t('Close') }}"></button>
					</div>
				@endif
				
				<div class="d-flex">
					<div class="toast-body">
						{!! $message !!}
					</div>
					
					@if (!$hasTitle)
						<button
								type="button"
								class="btn-close btn-close-white me-2 m-auto"
								data-bs-dismiss="toast"
								aria-label="{{ t('Close') }}"
						></button>
					@endif
				</div>
			</div>
		@endforeach
	</div>
	
	@pushonce('after_styles_stack')
		<style>
			.toast.text-bg-info a {
				color: rgba(var(--bs-light-rgb), var(--bs-text-opacity)) !important;
				font-weight: bold;
			}
		</style>
	@endpushonce
	
	@pushonce('after_scripts_stack')
		<script>
			onDocumentReady((event) => {
				const config = {!! $options !!};
				
				/* Initialize and show all toasts */
				const toastElements = document.querySelectorAll('.flash-toast');
				
				if (toastElements.length > 0) {
					toastElements.forEach(function (toastElement) {
						if (config.autohide !== true) {
							delete config.delay;
						}
						
						const toast = new bootstrap.Toast(toastElement, config);
						toast.show();
						
						/* Auto-remove from DOM after hiding */
						toastElement.addEventListener('hidden.bs.toast', function () {
							this.remove();
						});
					});
				}
			});
		</script>
	@endpushonce
@endif
