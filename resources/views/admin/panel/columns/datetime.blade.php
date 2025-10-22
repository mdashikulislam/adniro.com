{{-- localized datetime using jenssegers/date --}}
@php
	use App\Helpers\Common\Date\TimeZoneManager;
	use Illuminate\Support\Carbon;
	use App\Helpers\Common\Date;
	
	$columnValue = $entry->{$column['name']};
	try {
		$dateColumnValue = (new Carbon($columnValue))->timezone(TimeZoneManager::getContextualTimeZone());
	} catch (\Throwable $e) {
		$dateColumnValue = new Carbon($columnValue);
	}
@endphp
<span data-order="{{ $columnValue }}">
	{{ Date::format($dateColumnValue, 'datetime') }}
</span>
