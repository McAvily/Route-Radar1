<?php
require_once "../database.php";

$route_id = intval($_GET['route_id']);

$stmt = $conn->prepare(
  "SELECT station_name, lat, lng, station_order
   FROM bus_stations
   WHERE route_id=?
   ORDER BY station_order ASC"
);
$stmt->bind_param("i", $route_id);
$stmt->execute();
$res = $stmt->get_result();

$stations = [];
while ($row = $res->fetch_assoc()) {
  $stations[] = $row;
}

echo json_encode($stations);
