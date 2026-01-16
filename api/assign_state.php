<?php
session_start();
require_once "../database.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query(
        "SELECT user_id FROM rfid_assign_state WHERE active=1 LIMIT 1"
    );

    if ($res->num_rows === 0) {
        echo json_encode(["active" => false]);
    } else {
        $row = $res->fetch_assoc();
        echo json_encode([
            "active" => true,
            "user_id" => $row['user_id']
        ]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['user_id'])) {
        echo json_encode(["success" => false]);
        exit();
    }

    $user_id = intval($_POST['user_id']);

    $conn->query("DELETE FROM rfid_assign_state");

    $stmt = $conn->prepare(
        "INSERT INTO rfid_assign_state (user_id, active) VALUES (?,1)"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true]);
}
