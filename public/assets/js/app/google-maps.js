/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

if (typeof locationAddress === 'undefined') {
	var locationAddress = "New York City, United States";
}
if (typeof locationMapElId === 'undefined') {
	var locationMapElId = 'googleMaps';
}
if (typeof locationMapId === 'undefined') {
	var locationMapId = 'YOUR_MAP_ID_HERE';
}

function initGoogleMap() {
	const geocoder = new google.maps.Geocoder();
	
	geocoder.geocode({address: locationAddress}, (results, status) => {
		if (status === "OK") {
			const location = results[0].geometry.location;
			const locationMapEl = document.getElementById(locationMapElId);
			
			const map = new google.maps.Map(locationMapEl, {
				center: location,
				zoom: 9,
				zoomControl: true,
				mapTypeControl: false,
				scaleControl: false,
				streetViewControl: false,
				rotateControl: false,
				fullscreenControl: true,
				mapId: locationMapId // Optional but recommended for Advanced Markers
			});
			
			// Use AdvancedMarkerElement
			const marker = new google.maps.marker.AdvancedMarkerElement({
				map: map,
				position: location,
				title: locationAddress
			});
			
			// console.log("Latitude:", location.lat());
			// console.log("Longitude:", location.lng());
		} else {
			console.log("Geocode was not successful: " + status);
		}
	});
}
