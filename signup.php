<?php
include 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $cnic = $conn->real_escape_string($_POST['cnic']);
    $gender = $conn->real_escape_string($_POST['gender']);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo "Email already exists!";
    } else {
        $sql = "INSERT INTO users (fullname, email, password, cnic, gender)
                VALUES ('$fullname', '$email', '$password', '$cnic', '$gender')";
        if ($conn->query($sql) === TRUE) {
            header("Location: login.html");
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
?>
