// This example adds a search box to a map, using the Google Place Autocomplete
// feature. People can enter geographical searches. The search box will return a
// pick list containing a mix of places and predicted search terms.
// This example requires the Places library. Include the libraries=places
// parameter when you first load the API. For example:
// <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

var restrictionArray = ["si"];
var autocomplete;

let map;
let marker;
let autocomplete;

function initMap() {
    // Initialize the map, centered at a default location
    const defaultLocation = { lat: 46.5535346, lng: 15.6095304 };
    map = new google.maps.Map(document.getElementById("map_search"), {
        center: defaultLocation,
        zoom: 13,
        mapTypeId: "roadmap",
        streetViewControl: false,
        mapTypeControl: false,
    });

    // Set up a marker, initially hidden
    marker = new google.maps.Marker({
        map: map,
        position: defaultLocation,
        visible: false,
    });

    // Set up the autocomplete functionality on the search input
    const input = document.getElementById("pac-input");
    autocomplete = new google.maps.places.Autocomplete(input, {
        fields: ["place_id", "geometry", "name", "formatted_address"],
        types: ["geocode"],
    });
    autocomplete.bindTo("bounds", map);

    // Place the marker on the map when a place is selected
    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();

        if (!place.geometry || !place.geometry.location) {
            window.alert("No details available for input: '" + place.name + "'");
            return;
        }

        // Show the selected place on the map
        map.setCenter(place.geometry.location);
        map.setZoom(13);
        
        marker.setPosition(place.geometry.location);
        marker.setVisible(true);

        // Optionally, you can set these values to form fields if needed
        document.getElementById("a-name").value = place.name;
        document.getElementById("a-country").value = place.formatted_address;
    });
}

// Ensure the map is initialized when the page is fully loaded
window.onload = initMap;


function initAutoComplete() {
    // Initialize the map
    const map = new google.maps.Map(document.getElementById("map_search"), {
        center: { lat: 46.5535346, lng: 15.6095304 },
        zoom: 13,
        mapTypeId: "roadmap",
        streetViewControl: false,
        mapTypeControl: false
    });

    // Set up the Autocomplete input
    const input = document.getElementById("pac-input");
    const options = {
        region: 'EU',
        types: ['(regions)']
    };

    // Initialize the Autocomplete object
    autocomplete = new google.maps.places.Autocomplete(input, options);
    autocomplete.bindTo("bounds", map);
    autocomplete.setComponentRestrictions({
        country: restrictionArray
    });

    // Set up a marker to display the location
    const marker = new google.maps.Marker({
        map,
        anchorPoint: new google.maps.Point(0, -29)
    });

    // Add listener for when a place is selected
    autocomplete.addListener("place_changed", () => {
        marker.setVisible(false);
        const place = autocomplete.getPlace();

        if (!place.geometry || !place.geometry.location) {
            window.alert("No details available for input: '" + place.name + "'");
            return;
        }

        // If the place has a geometry, then present it on the map
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(13);
        }
        marker.setPosition(place.geometry.location);
        marker.setVisible(true);

        // Extract and populate the village/town name and country
        const placeData = getCountryAndPlace(place);
        document.getElementById("a-name").value = placeData.place;
        document.getElementById("a-country").value = placeData.country;
    });
}

function getCountryAndPlace(placeObj) {
    let place = "";
    let country = "";
    const address_components = placeObj.address_components;

    address_components.forEach(component => {
        const types = component.types;
        types.forEach(type => {
            if (type === "locality") {
                place = component.long_name;
            }
            if (type === "administrative_area_level_1" && !place) {
                place = component.long_name;
            }
            if (type === "country") {
                country = component.long_name;
            }
        });
    });

    return { place, country };
}

function setRestrictions() {
    const countrySelect = document.getElementById("aa-country");
    restrictionArray =  [countrySelect.value];
    autocomplete.setComponentRestrictions({
        country: restrictionArray
      });  
    document.getElementById("pac-input").value = "";
    document.getElementById("a-name").value = "";
    document.getElementById("a-country").value = "";
    
}

function disableLocationPicker(name, country) {

    var geocoder = new google.maps.Geocoder();
    var villageName = name + ", " + country;

    geocoder.geocode({ 'address': villageName }, function (results, status) {
       
        if (status === 'OK') {

            const map = new google.maps.Map(document.getElementById("map_search"), {
                center: results[0].geometry.location,
                zoom: 13,
                mapTypeId: "roadmap",
                streetViewControl: false,
                mapTypeControl: false
            });

            const marker = new google.maps.Marker({
                position: results[0].geometry.location,
                title: results[0].formatted_address,
                map: map
              });
        }
    });

    var input = document.getElementById("pac-input");
    input.disabled = true;
    input.value = villageName;

    const countrySelect = document.getElementById("aa-country");
    countrySelect.style.display = "none";

    document.getElementById("a-name").value = name;
    document.getElementById("a-country").value = country;
}

function disableLocationPickerIncludingKindAndInhibitants(name, country, kind, inhibitants) {

    disableLocationPicker(name, country);

    var inputKind = document.getElementById("a-kind");
    inputKind.disabled = true;
    inputKind.value = kind;

    var inputInhibitants = document.getElementById("a-inhibitants");
    inputInhibitants.disabled = true;
    inputInhibitants.value = inhibitants;

    document.getElementById("kind").value = kind;
    document.getElementById("inhibitants").value = inhibitants;

}