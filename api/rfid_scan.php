<?php
require_once "../database.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

if (!isset($_POST['uid'])) {
    http_response_code(400);
    echo json_encode(["error" => "UID missing"]);
    exit();
}

$uid = strtoupper(trim($_POST['uid']));

if (!preg_match('/^[0-9A-F:]+$/', $uid)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid UID format"]);
    exit();
}

$stmt = $conn->prepare(
    "SELECT user_id, fullname, credits, status
     FROM card_applications
     WHERE rfid_uid = ? AND status = 'Accepted'
     LIMIT 1"
);
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Card not registered"]);
    exit();
}

$user = $result->fetch_assoc();

echo json_encode([
    "success"  => true,
    "user_id"  => $user['user_id'],
    "name"     => $user['fullname'],
    "credits"  => $user['credits']
]);
