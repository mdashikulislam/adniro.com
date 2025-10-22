@php
	use App\Helpers\Common\Flash\FlashMessage;
	
	// Retrieve flash messages
	$flashMessages = FlashMessage::all();
	
	// Retrieve immediate flash messages (Without session store)
	if (isset($immediateMessages)) {
		$immediateMessages = is_array($immediateMessages) ? $immediateMessages : [];
        $flashMessages = array_merge($flashMessages, $immediateMessages);
    }
	
	$defaultOptions = (array)($presenterOptions ?? []);
	
	$messageList = collect($flashMessages)
		->values()
		->map(function ($flash) use ($defaultOptions) {
			$level = $flash['level'] ?? '';
			$title = $flash['title'];
			$message = $flash['message'];
			$icon = $flash['icon'] ?? null;
			$color = $flash['color'] ?? null;
			// $message = escapeStringForJs($message);
			
			$options = [
				'text'        => strip_tags($message),
				'html'        => $message,
				'icon'        => $icon,
				'customClass' => [
					'header' => "swal2-header bg-{$level}",
				],
				'confirmButtonText' => t('dismiss'),
			];
			
			$toast = (bool)($defaultOptions['toast'] ?? false);
			$hasTitle = (!empty($title) && $toast !== true);
			
			if ($hasTitle) {
				$options = collect($options)->prepend($title, 'title')->toArray();
			}
			
			$options = array_merge($defaultOptions, $options);
			
			// If toast is enabled, delete its incompatible parameters
			if ($toast) {
				if (array_key_exists('backdrop', $options)) {
					unset($options['backdrop']);
				}
				if (array_key_exists('focusConfirm', $options)) {
					unset($options['focusConfirm']);
				}
			}
			
			return $options;
		})->toJson();
@endphp

@pushonce('after_styles_stack')
	<style>
		.swal2-header {
			padding: 0.5rem 1rem;
			border-bottom: 1px solid #dee2e6;
		}
		
		.swal2-header.bg-success {
			background-color: #28a745 !important;
			color: #fff;
		}
		
		.swal2-header.bg-error {
			background-color: #dc3545 !important;
			color: #fff;
		}
		
		.swal2-header.bg-warning {
			background-color: #ffc107 !important;
			color: #000;
		}
		
		.swal2-header.bg-info {
			background-color: #17a2b8 !important;
			color: #fff;
		}
		
		.swal2-header.bg-question {
			background-color: #6c757d !important;
			color: #fff;
		}
		
		.swal2-title {
			margin: 0;
			font-size: 1.25rem;
		}
		
		.swal2-footer {
			justify-content: space-between;
		}
	</style>
@endpushonce

@pushonce('after_scripts_stack')
	<script>
		onDocumentReady((event) => {
			const messages = {!! $messageList !!};
			
			let currentIndex = 0;
			let timerInterval = null;
			
			function showNextAlert() {
				if (currentIndex >= messages.length) return;
				
				const options = messages[currentIndex];
				
				/* If toast is enabled, delete its incompatible parameters */
				if (options.toast === true) {
					if (options.hasOwnProperty('backdrop')) {
						delete options.backdrop;
					}
					if (options.hasOwnProperty('focusConfirm')) {
						delete options.focusConfirm;
					}
				}
				
				options.didOpen = (toast) => {
					toast.onmouseenter = Swal.stopTimer;
					toast.onmouseleave = Swal.resumeTimer;
				};
				
				options.willClose = () => {
					clearTimeout(timerInterval);
					currentIndex++;
					showNextAlert();
				};
				
				Swal.fire(options);
			}
			
			if (messages.length > 0) {
				showNextAlert();
			}
		});
	</script>
@endpushonce
