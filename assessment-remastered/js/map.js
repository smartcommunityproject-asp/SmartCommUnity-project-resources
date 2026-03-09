(function () {
	function initMap() {
		console.log("Initializing map...");

		const map = new google.maps.Map(document.getElementById("map"), {
			center: { lat: 46.056946, lng: 14.505751 }, // Latitude and Longitude of Ljubljana
			zoom: 9,
			mapTypeControl: false,
		});

		const input = document.getElementById("pac-input");
		const autocomplete = new google.maps.places.Autocomplete(input, {
			fields: ["name", "geometry"],
		});
		autocomplete.bindTo("bounds", map);

		const marker = new google.maps.Marker({
			map,
			anchorPoint: new google.maps.Point(0, -29),
			visible: false,
		});

		autocomplete.addListener("place_changed", () => {
			const place = autocomplete.getPlace();
			if (!place.geometry) {
				alert("No details available for this location.");
				return;
			}

			marker.setPosition(place.geometry.location);
			marker.setVisible(true);

			const placeName = place.name || "Unnamed location";
			document.getElementById("a-name").value = placeName;

			console.log("Place selected. Name:", placeName);
		});
	}

	window.initMap = initMap;
})();

document.addEventListener("DOMContentLoaded", function () {
	const form = document.querySelector("form");

	if (form) {
		form.addEventListener("submit", function () {
			const formData = new FormData(form);
			const formObject = {};
			formData.forEach((value, key) => {
				formObject[key] = value;
			});

			console.log("Form data being submitted:", formObject);
		});
	}
});
