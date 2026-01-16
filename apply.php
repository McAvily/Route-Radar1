<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'database.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, cnic, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $cnic, $gender);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Apply for Card</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
  
  <div class="container mt-5">
    <h2>Apply for Card</h2>
    <form action="submit_application.php" method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label>Name</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($name); ?>" disabled>
      </div>
      <div class="mb-3">
        <label>CNIC</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($cnic); ?>" disabled>
      </div>
      <div class="mb-3">
        <label>Gender</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($gender); ?>" disabled>
      </div>
      <div class="mb-3">
        <label>Age</label>
        <input type="number" name="age" class="form-control" required>
      </div>
      
      <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
  </div>
</body>
</html>
