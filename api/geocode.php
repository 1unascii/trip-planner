<?php
/**
 * Server-side proxy for Google Geocoding API.
 * Keeps the API key hidden from the browser.
 *
 * Query params:
 *   address — the address to geocode
 *
 * Returns the raw JSON response from Google Geocoding API.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$address = urlencode($_GET['address'] ?? '');

if (!$address) {
    http_response_code(400);
    echo json_encode(['error' => 'address is required']);
    exit;
}

$url = 'https://maps.googleapis.com/maps/api/geocoding/json'
    . '?address=' . $address
    . '&key=' . GOOGLE_API_KEY;

$response = file_get_contents($url);
echo $response;
