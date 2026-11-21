<?php

namespace App\Services\ListingAssistant;

use App\Models\City;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * Finds the listing's city, always inside the current country (adniro:ai).
 * First match wins: the browser's position, the place named in the text, the user's last listing.
 * Otherwise the form's own city picker handles it.
 */

class LocationResolver
{
	/** About 100 km: beyond that, the nearest city is probably not where the person is. */
	public const MAX_DEGREES = 1.0;
	
	/** A partial name needs at least this many characters, so "New" alone matches nothing. */
	public const MIN_PREFIX_LENGTH = 4;
	
	/**
	 * @return array{0: City|null, 1: string|null} The city and how it was found
	 */
	public function resolve(string $countryCode, ?float $lat, ?float $lon, ?string $text, ?User $user): array
	{
		if (!is_null($lat) && !is_null($lon) && abs($lat) <= 90 && abs($lon) <= 180) {
			$city = $this->nearest($countryCode, $lat, $lon);
			if ($city) {
				return [$city, 'position'];
			}
		}
		
		if (!empty(trim((string)$text))) {
			$city = $this->byName($countryCode, (string)$text);
			if ($city) {
				return [$city, 'text'];
			}
		}
		
		if ($user) {
			$cityId = Post::query()->withoutGlobalScopes()
				->where('user_id', $user->getKey())
				->where('country_code', $countryCode)
				->orderByDesc('id')
				->value('city_id');
			$city = $cityId ? City::query()->find($cityId) : null;
			if ($city) {
				return [$city, 'last_listing'];
			}
		}
		
		return [null, null];
	}
	
	private function nearest(string $countryCode, float $lat, float $lon): ?City
	{
		$cosLat = cos(deg2rad($lat));
		
		$row = DB::table('cities')
			->where('country_code', $countryCode)
			->where('active', 1)
			->whereBetween('latitude', [$lat - self::MAX_DEGREES, $lat + self::MAX_DEGREES])
			->whereBetween('longitude', [$lon - self::MAX_DEGREES / max($cosLat, 0.1), $lon + self::MAX_DEGREES / max($cosLat, 0.1)])
			->selectRaw('id, (POW(latitude - ?, 2) + POW((longitude - ?) * ?, 2)) as d2', [$lat, $lon, $cosLat])
			->orderBy('d2')
			->first();
		
		return $row ? City::query()->find($row->id) : null;
	}
	
	/**
	 * The place named in the text: an exact city name first, then a city whose name starts with
	 * the given words ("New York" finds "New York City"). The most populated city wins a tie.
	 */
	private function byName(string $countryCode, string $text): ?City
	{
		$parts = array_values(array_filter(
			array_slice(array_map('trim', preg_split('/[,;\/]+/u', $text) ?: []), 0, 3),
			fn ($part) => mb_strlen($part) >= 2
		));
		
		// Names are stored as JSON translations, e.g. {"en":"Dhaka"}: match a whole translated value
		foreach ($parts as $part) {
			$city = $this->firstCity($countryCode, '%"' . self::escapeLike($part) . '"%', $part);
			if ($city) {
				return $city;
			}
		}
		
		// The start of a name, ending at a word
		foreach ($parts as $part) {
			if (mb_strlen($part) < self::MIN_PREFIX_LENGTH) {
				continue;
			}
			$city = $this->firstCity($countryCode, '%"' . self::escapeLike($part) . ' %');
			if ($city) {
				return $city;
			}
		}
		
		return null;
	}
	
	private function firstCity(string $countryCode, string $pattern, ?string $plainName = null): ?City
	{
		$row = DB::table('cities')
			->where('country_code', $countryCode)
			->where('active', 1)
			->where(function ($query) use ($pattern, $plainName) {
				$query->where('name', 'like', $pattern);
				if (!is_null($plainName)) {
					$query->orWhere('name', $plainName);
				}
			})
			->orderByDesc('population')
			->first(['id']);
		
		return $row ? City::query()->find($row->id) : null;
	}
	
	private static function escapeLike(string $value): string
	{
		return addcslashes($value, '%_"\\');
	}
}
