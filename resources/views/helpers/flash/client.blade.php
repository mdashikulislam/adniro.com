@php
	use App\Helpers\Common\JsonUtils;
	
	// PNotify =============================================================
	$pnotifyGlobalOptions = (array)config("larapen.flash.presenters.pnotify", []);
	
	// Get PNotify"s defaults options
	$pnotifyDefaultOptions = (array)($pnotifyGlobalOptions['defaultOptions'] ?? []);
	$pnotifyDefaultOptions['textTrusted'] = true;
	$pnotifyDefaultOptions['labels'] = [
		'close'   => t('Close'),
		'stick'   => t('pin'),
		'unstick' => t('unpin'),
	];
	$pnotifyDefaultOptions = JsonUtils::ensureJson($pnotifyDefaultOptions);
	
	// Get PNotify's default stack
	$pnotifyDefaultStack = (array)($pnotifyGlobalOptions['defaultStack'] ?? []);
	$pnotifyDefaultStack = JsonUtils::ensureJson($pnotifyDefaultStack);
@endphp

<script>
	onDocumentReady((event) => {
		{{-- PNotify --}}
		if (typeof PNotify !== 'undefined') {
			/* Load Modules */
			PNotify.defaultModules.set(PNotifyFontAwesome5Fix, {});
			PNotify.defaultModules.set(PNotifyFontAwesome5, {});
			
			/* Configure PNotify custom defaults */
			let defaultStack = {!! $pnotifyDefaultStack !!};
			defaultStack = ensureJsonObject(defaultStack);
			
			let defaultsOptions = {!! $pnotifyDefaultOptions !!};
			defaultsOptions = ensureJsonObject(defaultsOptions);
			defaultsOptions.stack = new PNotify.Stack(defaultStack);
			
			/* Change the PNotify defaults */
			PNotify.defaults = Object.assign(PNotify.defaults, defaultsOptions);
		}
	});
	
	/**
	 * Show a PNotify alert (Using the Stack feature)
	 * @param type
	 * @param message
	 * @param title
	 */
	function pnotifyAlertClient(type, message, title = '') {
		if (typeof PNotify === 'undefined') {
			return;
		}
		
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
