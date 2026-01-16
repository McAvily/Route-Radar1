<?php
require_once "../database.php";

if (!isset($_POST['uid'], $_POST['user_id'])) {
    http_response_code(400);
    exit();
}

$uid = strtoupper(trim($_POST['uid']));
$user_id = intval($_POST['user_id']);

$stmt = $conn->prepare(
  "UPDATE card_applications SET rfid_uid=? WHERE id=?"
);
$stmt->bind_param("si", $uid, $user_id);
$stmt->execute();
$stmt->close();

$conn->query("DELETE FROM rfid_assign_state");

echo json_encode(["success" => true]);
