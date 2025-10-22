@php
	use App\Helpers\Common\BsThemeGenerator;
	
	$css = '';
	if (!empty($primaryBgColor)) {
		$primaryColor = $primaryBgColor;
		$generator = new BsThemeGenerator($primaryColor);
		
		$css = $generator->generateCSS();
		// dd($css); // Debug
		
		// Generate and save CSS file
		// $cssFile = $generator->saveCSSToFile('css/custom-bootstrap.css');
	}
@endphp
<style>
	@if (!empty($css))
		{!! $css !!}
	@endif
</style>
