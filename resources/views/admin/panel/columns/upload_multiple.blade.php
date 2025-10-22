@php
	$disk = $column['disk'] ?? null;
	$value = $entry->{$column['name']};
@endphp
<span>
    @if (!empty($value))
		@foreach ($value as $filePath)
			@php
				$fileUri = !empty($disk) ? \Storage::disk($disk)->url($filePath) : $filePath;
				$fileUrl = asset($fileUri);
			@endphp
			- <a target="_blank" href="{{ $fileUrl }}">{{ $filePath }}</a><br>
		@endforeach
	@else
		-
	@endif
</span>
