<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $age = $_POST['age'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if (empty($age) || empty($user_type)) {
        die("Age and User Type are required.");
    }

    $stmt = $conn->prepare("SELECT fullname, cnic, gender FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fullname, $cnic, $gender);
    $stmt->fetch();
    $stmt->close();

    $check = $conn->prepare("SELECT id FROM card_applications WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        header("Location: dashboard.php?error=already_applied");
        exit();
    }
    $check->close();

    $insert = $conn->prepare("INSERT INTO card_applications (user_id, fullname, cnic_photo, gender, age, user_role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $insert->bind_param("isssis", $user_id, $fullname, $cnic, $gender, $age, $user_type);

    if (!$insert->execute()) {
        die("Error inserting application: " . $insert->error);
    }

    $insert->close();

    if ($user_type === "Student") {
        $institution_name = $_POST['institution_name'] ?? '';
        $admission_number = $_POST['admission_number'] ?? '';
        $year_of_education = $_POST['year_of_education'] ?? '';

        if (!empty($institution_name) && !empty($admission_number) && !empty($year_of_education)) {
            $subsidy = $conn->prepare("INSERT INTO student_subsidy (user_id, institution_name, admission_number, year_of_education) VALUES (?, ?, ?, ?)");
            $subsidy->bind_param("isss", $user_id, $institution_name, $admission_number, $year_of_education);

            if (!$subsidy->execute()) {
                die("Error saving student subsidy info: " . $subsidy->error);
            }

            $subsidy->close();
        }
    }

    header("Location: dashboard.php?success=1");
    exit();
} else {
    echo "Invalid request method.";
}
?>
