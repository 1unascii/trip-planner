/**
 * ════════════════════════════════════════════════════════════════
 * TRIP PLANNER MAP
 * ════════════════════════════════════════════════════════════════
 * Renders a Google Map inside an iframe and draws driving routes
 * between stops passed via URL parameters.
 *
 * URL format:
 *   index.php?center=Madison+WI&addresses=Rockford%2C+IL|Mason+City%2C+IA
 *
 * Parameters:
 *   center    — address to center the map on (usually the first stop)
 *   addresses — stops separated by | (pipe), or "0" for no stops
 *
 * Route drawing flow:
 *   ┌────────────┐     ┌─────────────┐     ┌──────────────┐
 *   │ URL params │────>│ geocode     │────>│ create map   │
 *   │ center=... │     │ center addr │     │ at coords    │
 *   │ addresses= │     └─────────────┘     └──────┬───────┘
 *   │ A|B|C      │                                │
 *   └────────────┘                                v
 *                                          ┌──────────────┐
 *                                          │ drawRoute()  │
 *                                          │ A ──> B ──> C│
 *                                          │ (Directions  │
 *                                          │  API)        │
 *                                          └──────────────┘
 */

// ── PARSE URL PARAMETERS ───────────────────────────────────────
// Reads ?key=value&key2=value2 from the iframe's URL into $_GET.
// Handles URL encoding (+ becomes space, %XX decoded).
var $_GET = {};

document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
    function decode(s) {
        return decodeURIComponent(s.split("+").join(" "));
    }
    $_GET[decode(arguments[1])] = decode(arguments[2]);
});

/**
 * initMap() — Google Maps callback
 * Called automatically when the Google Maps API script loads
 * (via the &callback=initMap parameter in the script tag).
 */
function initMap() {

    // ── MAP STYLING ────────────────────────────────────────────
    // Desaturated grey theme with dark water
    var styles = [
        { "stylers": [{ "hue": "#ff1a00" }, { "saturation": -100 }, { "lightness": 33 }, { "gamma": 0.5 }] },
        { "featureType": "water", "elementType": "geometry", "stylers": [{ "color": "#2D333C" }] }
    ];

    // ── PARSE ADDRESSES ────────────────────────────────────────
    // Split on | (pipe) since addresses contain commas.
    // "0" means no addresses (initial load with empty sidebar).
    var rawAddresses = $_GET['addresses'];
    var addresses = (rawAddresses && rawAddresses !== '0') ? rawAddresses.split("|") : [];
    var center = $_GET['center'] || 'Madison WI';

    // ── GEOCODE THE CENTER ADDRESS ─────────────────────────────
    // Convert the center address string to lat/lng coordinates,
    // then create the map at that location.
    var geocoder = new google.maps.Geocoder();
    var map;

    geocoder.geocode({ 'address': center }, function(results, status) {
        if (status !== google.maps.GeocoderStatus.OK) {
            // Fallback to Madison, WI if geocoding fails
            createMap({ lat: 43.0731, lng: -89.4012 });
            return;
        }
        createMap({
            lat: results[0].geometry.location.lat(),
            lng: results[0].geometry.location.lng()
        });
    });

    /**
     * createMap(centerCoords)
     * Builds the map object with custom styling, then either draws
     * a route (2+ stops) or places a single marker (1 stop).
     *
     *   2+ stops:  drawRoute() handles markers + route line
     *   1 stop:    placeMarker() drops a single pin
     *   0 stops:   just the map, no markers
     */
    function createMap(centerCoords) {
        var styledMap = new google.maps.StyledMapType(styles, { name: "Styled Map" });

        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 9,
            center: new google.maps.LatLng(centerCoords.lat, centerCoords.lng),
            mapTypeControlOptions: {
                mapTypeIds: [google.maps.MapTypeId.ROADMAP, 'map_style']
            }
        });

        // Apply the custom grey style
        map.mapTypes.set('map_style', styledMap);
        map.setMapTypeId('map_style');

        if (addresses.length >= 2) {
            drawRoute(map, addresses);
        } else if (addresses.length === 1) {
            placeMarker(map, geocoder, addresses[0]);
        }
    }

    /**
     * placeMarker(map, geocoder, address)
     * Geocodes a single address and drops a marker on the map.
     * Used when there's only one stop (no route to draw).
     */
    function placeMarker(map, geocoder, address) {
        geocoder.geocode({ 'address': address }, function(results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                new google.maps.Marker({
                    position: results[0].geometry.location,
                    map: map,
                    title: address
                });
            }
        });
    }

    /**
     * drawRoute(map, stops)
     * Uses the Google Directions API to calculate and render a
     * driving route between all stops.
     *
     * The first stop is the origin, the last is the destination,
     * and everything in between becomes waypoints:
     *
     *   stops = ["NYC", "Chicago", "Denver", "LA"]
     *
     *   origin:      NYC
     *   waypoints:   [Chicago, Denver]   (stopover: true = stop here)
     *   destination: LA
     *
     *   ┌─────┐     ┌─────────┐     ┌────────┐     ┌────┐
     *   │ NYC │────>│ Chicago │────>│ Denver │────>│ LA │
     *   └─────┘     └─────────┘     └────────┘     └────┘
     *   origin       waypoint        waypoint     destination
     *
     * optimizeWaypoints: true tells Google to reorder the
     * waypoints for the shortest total driving distance.
     * The origin and destination stay fixed.
     *
     * DirectionsRenderer draws the blue route line and places
     * labeled markers (A, B, C, D) at each stop automatically.
     */
    function drawRoute(map, stops) {
        var directionsService = new google.maps.DirectionsService();
        var directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: false   // show A, B, C markers
        });

        var origin = stops[0];
        var destination = stops[stops.length - 1];

        // Build waypoints array from the middle stops
        var waypoints = [];
        for (var i = 1; i < stops.length - 1; i++) {
            waypoints.push({
                location: stops[i],
                stopover: true
            });
        }

        directionsService.route({
            origin: origin,
            destination: destination,
            waypoints: waypoints,
            optimizeWaypoints: false,  // draw the route in the order given, don't reorder
            travelMode: google.maps.TravelMode.DRIVING
        }, function(response, status) {
            if (status === 'OK') {
                // DirectionsRenderer draws the route line and markers
                directionsRenderer.setDirections(response);
            } else {
                alert('Could not calculate route: ' + status);
            }
        });
    }
}
