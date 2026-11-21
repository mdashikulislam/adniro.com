{{--
 * adniro: a numbered card around one part of the listing form (redesigned layout).
 * The number comes from a CSS counter, so hidden cards leave no gap.
 *   key, title, sub; collapsible + open (a <details>)
--}}
@php
	$cardId = 'lfSection-' . $key;
	$sub ??= null;
	$collapsible ??= false;
	$open ??= false;
@endphp
@if ($collapsible)
	<details class="lf-card lf-card--{{ $key }}" id="{{ $cardId }}" @if ($open) open @endif>
		<summary class="lf-card-head">
			<span class="lf-card-num" aria-hidden="true"></span>
			<span class="lf-card-heading">
				<span class="lf-card-title" id="{{ $cardId }}-title">{{ $title }}</span>
				@if (!empty($sub))
					<span class="lf-card-sub">{{ $sub }}</span>
				@endif
			</span>
			<i class="bi bi-chevron-down lf-card-chevron" aria-hidden="true"></i>
		</summary>
		<div class="row lf-card-body">
			{{ $slot }}
		</div>
	</details>
@else
	<section class="lf-card lf-card--{{ $key }}" id="{{ $cardId }}" aria-labelledby="{{ $cardId }}-title">
		<header class="lf-card-head">
			<span class="lf-card-num" aria-hidden="true"></span>
			<span class="lf-card-heading">
				<h2 class="lf-card-title" id="{{ $cardId }}-title">{{ $title }}</h2>
				@if (!empty($sub))
					<p class="lf-card-sub">{{ $sub }}</p>
				@endif
			</span>
		</header>
		<div class="row lf-card-body">
			{{ $slot }}
		</div>
	</section>
@endif
