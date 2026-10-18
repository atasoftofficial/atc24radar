<?php
// ATASOFT ATC24 Radar - approach chart list endpoint.
//
// Replaces the old Node/Express `GET /approaches/:icao` route. Pure
// filesystem listing under public/assets/maps/<ICAO> - no upstream call
// needed. Called as approaches.php?icao=IRFD.

header('Content-Type: application/json; charset=utf-8');

$icao = strtoupper(trim($_GET['icao'] ?? ''));
if (!preg_match('/^[A-Z]{4}$/', $icao)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid airport code']);
    exit;
}

$folder = __DIR__ . '/public/assets/maps/' . $icao;

if (!is_dir($folder)) {
    http_response_code(404);
    echo json_encode(['error' => "Airport not found, $folder"]);
    exit;
}

$svgs = array_values(array_filter(scandir($folder), function ($file) {
    return substr($file, -4) === '.svg' && strtoupper($file) !== 'GROUND.SVG';
}));

echo json_encode($svgs);
