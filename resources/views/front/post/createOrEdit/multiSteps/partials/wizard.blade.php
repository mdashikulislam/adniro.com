@if (!empty($wizardMenu))
	@include('front.common.spacer')
	<div class="container">
	    <div class="row">
	        <div class="col-12 mt-md-1 mt-sm-0 mt-0">
		        <ul class="nav nav-pills border border-primary rounded bg-body-tertiary p-2 fs-6 fw-bold">
			        @foreach($wizardMenu as $menu)
				        @continue(!$menu['included'])
				        @php
				            $stepClass = $menu['class'] ?? null;
							$stepClass = !empty($stepClass) ? ' ' . $stepClass : '';
							$stepUrl = $menu['url'] ?? null;
							$stepLabel = $menu['label'] ?? '--';
				        @endphp
				        <li class="nav-item">
					        @if (!empty($menu['url']))
								@if (str_contains($stepClass, 'active'))
						            <a class="nav-link{{ $stepClass }}" aria-current="page" href="{{ $stepUrl }}">
							            {{ $stepLabel }}
						            </a>
						        @else
							        <a class="nav-link{{ $stepClass }}" href="{{ $stepUrl }}">
								        {{ $stepLabel }}
							        </a>
						        @endif
					        @else
						        <a class="nav-link disabled{{ $stepClass }}">
							        {{ $stepLabel }}
						        </a>
					        @endif
				        </li>
			        @endforeach
		        </ul>
	        </div>
	    </div>
	</div>
@endif
