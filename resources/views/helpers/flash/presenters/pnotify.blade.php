{{-- resources/views/components/flash-messages.blade.php --}}
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

@pushonce('after_scripts_stack')
	@if (!empty($flashMessages))
		@php
			$globalOptions = (array)($presenterOptions ?? []);
			
			// Get defaults options
			$defaultOptions = (array)($globalOptions['defaultOptions'] ?? []);
			$defaultOptions['textTrusted'] = true;
			$defaultOptions['labels'] = [
				'close'   => t('Close'),
				'stick'   => t('pin'),
				'unstick' => t('unpin'),
			];
			$defaultOptions = JsonUtils::ensureJson($defaultOptions);
			
			// Get default stack
			$defaultStack = (array)($globalOptions['defaultStack'] ?? []);
			$defaultStack = JsonUtils::ensureJson($defaultStack);
		@endphp
		<script>
			onDocumentReady((event) => {
				/* Check if PNotify is loaded */
				if (typeof PNotify === 'undefined') {
					console.error('PNotify library is not loaded. Please include PNotify CSS and JS files.');
					return;
				}
				
				/* Load Modules */
				PNotify.defaultModules.set(PNotifyFontAwesome5Fix, {});
				PNotify.defaultModules.set(PNotifyFontAwesome5, {});
				
				/* Configure PNotify custom defaults */
				let defaultStack = {!! $defaultStack !!};
				defaultStack = ensureJsonObject(defaultStack);
				
				let defaultsOptions = {!! $defaultOptions !!};
				defaultsOptions = ensureJsonObject(defaultsOptions);
				defaultsOptions.stack = new PNotify.Stack(defaultStack);
				
				/* Change the PNotify defaults */
				PNotify.defaults = Object.assign(PNotify.defaults, defaultsOptions);
				
				@foreach($flashMessages as $flash)
				@php
					$level = $flash['level'] ?? '';
					$type = $flash['type'];
					$message = $flash['message'];
					$escapedMessage = escapeStringForJs($message);
					$title = $flash['title'] ?? null;
					
					$hasTitle = !empty($title);
				@endphp
				
				$(function () {
					const type = "{{ $type }}";
					const message = "{!! $escapedMessage !!}";
					const title = "{{ $title }}";
					
					@if ($hasTitle)
					pnotifyAlert(type, message, title);
					@else
					pnotifyAlert(type, message);
					@endif
				});
				@endforeach
			});
			
			/**
			 * Show a PNotify alert (Using the Stack feature)
			 * @param type
			 * @param message
			 * @param title
			 */
			function pnotifyAlert(type, message, title = '') {
				const alertParams = {
					text: message,
					type: 'info'
				};
				
				switch (type) {
					case 'error':
						alertParams.type = 'error';
						break;
					case 'warning':
						alertParams.type = 'notice';
						break;
					case 'notice':
						alertParams.type = 'notice';
						break;
					case 'info':
						alertParams.type = 'info';
						break;
					case 'success':
						alertParams.type = 'success';
						break;
				}
				
				if (typeof title !== 'undefined' && title !== '' && title.length > 0) {
					alertParams.title = title;
					alertParams.icon = true;
				}
				
				PNotify.alert(alertParams);
			}
		</script>
	@endif
@endpushonce
