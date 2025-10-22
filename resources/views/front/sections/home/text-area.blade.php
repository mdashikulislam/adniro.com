@php
	$sectionOptions = $textAreaOptions ?? [];
	
	$appLang = config('appLang.code');
	$locale = config('app.locale');
	
	// Fallback Language
	$textTitle = $sectionOptions["title_{$appLang}"] ?? '';
	$textBody = $sectionOptions["body_{$appLang}"] ?? '';
	
	// Current Language
	$textTitle = $sectionOptions["title_{$locale}"] ?? $textTitle;
	$textBody = $sectionOptions["body_{$locale}"] ?? $textBody;
	
	// Replace Global Patterns
	$textTitle = replaceGlobalPatterns($textTitle);
	$textBody = replaceGlobalPatterns($textBody);
	
	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';
	
	$htmlAttr = $sectionOptions['html_attributes'] ?? '';
	$htmlAttr = !empty($htmlAttr) ? " {$htmlAttr}" : '';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
@endphp
@if (!empty($textTitle) || !empty($textBody))
	<div class="container{{ $cssClasses }}" style="{!! $style !!}">
		<div class="card"{!! $htmlAttr !!}>
			<div class="card-body">
				@if (!empty($textTitle))
					<h4 class="card-title fw-bold border-bottom pb-3 mb-3">{{ $textTitle }}</h4>
				@endif
				@if (!empty($textBody))
					<div>{!! $textBody !!}</div>
				@endif
			</div>
		</div>
	</div>
@endif
