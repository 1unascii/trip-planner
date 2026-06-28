<?php
/**
 * Server-side proxy for Google Directions API.
 * Keeps the API key hidden from the browser.
 *
 * Query params (passed through from the frontend):
 *   origin      — starting address
 *   destination — ending address
 *   waypoints   — pipe-separated intermediate stops (optional)
 *   optimize    — "true" to optimize waypoint order (optional)
 *
 * Returns the raw JSON response from Google Directions API.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$origin = urlencode($_GET['origin'] ?? '');
$destination = urlencode($_GET['destination'] ?? '');
$waypoints = $_GET['waypoints'] ?? '';
$optimize = $_GET['optimize'] ?? 'false';

if (!$origin || !$destination) {
    http_response_code(400);
    echo json_encode(['error' => 'origin and destination are required']);
    exit;
}

$url = 'https://maps.googleapis.com/maps/api/directions/json'
    . '?origin=' . $origin
    . '&destination=' . $destination
    . '&key=' . GOOGLE_API_KEY;

if ($waypoints) {
    $prefix = $optimize === 'true' ? 'optimize:true|' : '';
    $url .= '&waypoints=' . urlencode($prefix . $waypoints);
}

$response = file_get_contents($url);
echo $response;
