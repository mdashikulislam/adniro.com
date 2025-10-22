@php
	$scrollCue ??= null;
@endphp

{{-- Animated Mouse Graphic (at Bottom) --}}
@if ($scrollCue == 'mouseGraphic')
	<div class="scroll-indicator">
		<svg width="30" height="50" viewBox="0 0 30 50" fill="none">
			<rect x="1" y="1" width="28" height="48" rx="14" stroke="white" stroke-width="2"/>
			<circle cx="15" cy="15" r="4" fill="white">
				<animate attributeName="cy" from="15" to="35" dur="1.5s" repeatCount="indefinite"/>
			</circle>
		</svg>
	</div>
@endif

{{-- Animated Chevron Arrow & Text (at Bottom) --}}
@if ($scrollCue == 'chevronText')
	<div class="scroll-cue">
		<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<span>{{ t('scroll_down') }}</span>
	</div>
@endif

{{-- Top Progress Bar / Scroll Position Indicator --}}
@if ($scrollCue == 'progressBar')
	<div class="progress-indicator" aria-hidden="true"></div>
@endif

@section('after_styles')
	@parent
	@if (in_array($scrollCue, ['mouseGraphic', 'chevronText']))
		<style>
			@if ($scrollCue == 'mouseGraphic')
				.scroll-indicator {
					position: absolute;
					bottom: 30px;
					left: 50%;
					transform: translateX(-50%);
					animation: bounce-scroll-indicator 2s infinite;
				}
				
				@keyframes bounce-scroll-indicator {
					0%, 20%, 50%, 80%, 100% {
						transform: translateX(-50%) translateY(0);
					}
					40% {
						transform: translateX(-50%) translateY(-20px);
					}
					60% {
						transform: translateX(-50%) translateY(-10px);
					}
				}
			@endif
			
			@if ($scrollCue == 'chevronText')
				.scroll-cue {
					position: absolute;
					bottom: 30px; /* Distance from bottom of hero */
					left: 50%;
					transform: translateX(-50%);
					display: flex;
					flex-direction: column;
					align-items: center;
					color: rgba(255, 255, 255, 0.8); /* Semi-transparent white; match your theme */
					font-size: 14px;
					font-weight: 300;
					letter-spacing: 1px;
					opacity: 0;
					animation: bounce-scroll-cue 2s infinite;
					z-index: 10; /* Ensure it stays on top */
				}
				
				.scroll-cue svg {
					margin-bottom: 2px; /* Default: 4px */
					animation: bounce-scroll-cue-arrow 1.5s infinite; /* Slightly offset for layered effect */
				}
				
				/* Keyframe for text fade/bounce */
				@keyframes bounce-scroll-cue {
					0%, 20%, 50%, 80%, 100% {
						opacity: 1;
						transform: translateX(-50%) translateY(0);
					}
					40% {
						opacity: 0.7;
						transform: translateX(-50%) translateY(-10px);
					}
					60% {
						opacity: 0.7;
						transform: translateX(-50%) translateY(-5px);
					}
					70%, 90% {
						opacity: 0;
					}
				}
				
				/* Keyframe for arrow bounce (faster, downward focus) */
				@keyframes bounce-scroll-cue-arrow {
					0% {
						transform: translateY(0);
					}
					50% {
						transform: translateY(8px); /* Bounces down */
					}
					100% {
						transform: translateY(0);
					}
				}
			@endif
			
			/* Hide on small screens if needed (e.g., mobile has natural scroll cues) */
			@media (max-width: 576px) {
				.scroll-indicator, .scroll-cue {
					display: none;
				}
			}
		</style>
	@endif
	@if ($scrollCue == 'progressBar')
		<style>
			.progress-indicator {
				position: fixed;
				top: 0;
				left: 0;
				height: 4px;
				width: 0;
				background: rgba(var(--bs-primary-rgb),var(--bs-bg-opacity,1)); /* Bootstrap primary color */
				z-index: 3000;
				transition: width 0.25s ease-out;
			}
		</style>
	@endif
@endsection

@section('after_scripts')
	@parent
	@if (in_array($scrollCue, ['mouseGraphic', 'chevronText']))
		<script>
			onDocumentReady((event) => {
				@if ($scrollCue == 'mouseGraphic')
					document.addEventListener("scroll", () => {
						const indicator = document.querySelector(".scroll-indicator");
						if (!indicator) return;
						
						/* Once user scrolls a bit */
						const offset = 50;
						if (window.pageYOffset > offset) {
							indicator.style.opacity = '0';
							indicator.style.transition = 'opacity 0.5s ease-out';
						}
						if (window.pageYOffset <= offset) {
							indicator.style.opacity = '100%';
							indicator.style.transition = 'opacity 0.5s ease-in';
						}
					});
				@endif
				@if ($scrollCue == 'chevronText')
					document.addEventListener("scroll", () => {
						const cue = document.querySelector(".scroll-cue");
						if (!cue) return;
						
						/* Once user scrolls a bit */
						const offset = 300;
						if (window.pageYOffset > offset) {
							cue.style.display = 'none';
						}
						if (window.pageYOffset <= offset) {
							cue.style.display = 'flex';
						}
					});
				@endif
			});
		</script>
	@endif
	@if ($scrollCue == 'progressBar')
		<script>
			onDocumentReady((event) => {
				{{-- Shows how far down the page the user is, but also acts as a hint that the page scrolls. --}}
				window.addEventListener("scroll", () => {
					const indicator = document.querySelector(".progress-indicator");
					if (!indicator) return;
					
					const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
					const docHeight = document.documentElement.scrollHeight - window.innerHeight;
					const scrollPct = (scrollTop / docHeight) * 100;
					indicator.style.width = scrollPct + "%";
				});
			});
		</script>
	@endif
@endsection
