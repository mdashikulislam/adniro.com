@section('body_class', 'page-listing-form')
@if (!empty($wizardMenu))
	@include('front.common.spacer')
	<div class="container">
	    <div class="row">
	        <div class="col-12 mt-md-1 mt-sm-0 mt-0">
		        <ul class="nav listing-wizard">
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
							            <span class="listing-wizard-num"></span><span class="listing-wizard-label">{{ $stepLabel }}</span>
						            </a>
						        @else
							        <a class="nav-link{{ $stepClass }}" href="{{ $stepUrl }}">
								        <span class="listing-wizard-num"></span><span class="listing-wizard-label">{{ $stepLabel }}</span>
							        </a>
						        @endif
					        @else
						        <a class="nav-link disabled{{ $stepClass }}">
							        <span class="listing-wizard-num"></span><span class="listing-wizard-label">{{ $stepLabel }}</span>
						        </a>
					        @endif
				        </li>
			        @endforeach
		        </ul>
	        </div>
	    </div>
	</div>
@endif
