<?php
header("Content-Type: application/json");

if (
    !isset($_GET['route']) ||
    !isset($_GET['lat']) ||
    !isset($_GET['lng'])
) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters"]);
    exit();
}

$route = intval($_GET['route']);
$lat   = floatval($_GET['lat']);
$lng   = floatval($_GET['lng']);
$speed = isset($_GET['speed']) ? floatval($_GET['speed']) : 0;

if ($route !== 4) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid route"]);
    exit();
}

$data = [
    "route" => $route,
    "lat"   => $lat,
    "lng"   => $lng,
    "speed" => $speed,
    "time"  => time()
];

$file = __DIR__ . "/route4_live.json";
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

echo json_encode(["status" => "OK"]);
