function initGoodPracticesMap() {
	const mapElement = document.getElementById("good-practices-map");
	if (!mapElement) {
		console.error("Map container not found!");
		return;
	}

	const locations = GoodPracticesData.locations;

	const map = new google.maps.Map(mapElement, {
		center: { lat: 0, lng: 0 },
		zoom: 2,
		zoomControl: true, // Enable zoom controls
		fullscreenControl: true, // Enable fullscreen button
		mapTypeControl: false, // Hide map type switcher if not needed
	});

	// Adjust control positions (optional)
	map.setOptions({
		zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_TOP },
		fullscreenControlOptions: {
			position: google.maps.ControlPosition.RIGHT_TOP,
		},
	});

	const bounds = new google.maps.LatLngBounds();

	locations.forEach((location) => {
		const marker = new google.maps.Marker({
			position: {
				lat: parseFloat(location.lat),
				lng: parseFloat(location.lng),
			},
			map: map,
			title: location.title,
		});

		const infoWindow = new google.maps.InfoWindow({
			content: location.content,
		});

		marker.addListener("click", () => {
			infoWindow.open(map, marker);
		});

		bounds.extend(marker.getPosition());
	});

	if (locations.length > 0) {
		map.fitBounds(bounds);
	}
}
