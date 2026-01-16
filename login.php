<?php
include 'database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['fullname'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['user_id'] = $row['id'];
            
            


            if ($row['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "User not found.";
    }
}
?>
