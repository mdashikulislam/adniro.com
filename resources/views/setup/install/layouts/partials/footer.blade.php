<footer>
	<div class="container-fluid border-top bg-body-tertiary py-5 mt-5">
		<div class="container p-0 my-0">
			
			<div class="row">
				<div class="col-xl-12 col-md-12">
					
					<div class="text-center">
						&copy; {{ date('Y') }} <a href="{{ url('/') }}" class="{{ linkClass() }}">{{ strtolower(getDomain()) }}</a>
					</div>
					
				</div>
			</div>
			
		</div>
	</div>
</footer>
