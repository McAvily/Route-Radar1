<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

require_once "database.php";

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, cnic_photo, gender, age, created_at, status, user_role, credits FROM card_applications WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT fullname, cnic, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $cnic, $gender);
$stmt->fetch();
$stmt->close();

$credits = $application['credits'] ?? 0;

$API_KEY = '';
?>
<!DOCTYPE html>
<html>
<head>
  <title>User Dashboard</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="dashboard.js"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <div class="img"></div>
        <span class="brand-title">Route Radar</span>
      </a>
      <button class="navbar-toggler d-lg-none" type="button" id="mobileMenuBtn">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse d-none d-lg-flex desktop-menu-wrapper">
          <ul class="nav nav-tabs custom-tabs nav-justified ms-auto desktop-menu">
              <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Dashboard</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#application">Card Application</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#apply">Apply for a Card</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#routes">Routes</button></li>
              <li class="nav-item"><a class="nav-link info-tab" href="#">Information</a></li>
              <li class="nav-item"><a class="nav-link" href="#">Feedback</a></li>
              <li class="nav-item"><a class="nav-link" href="logout.php">Sign Out</a></li>
          </ul>
      </div>
    </div>
  </nav>

  <div id="mobileSideMenu" class="mobile-side-menu d-lg-none">
    <button id="closeMenu" class="close-btn">&times;</button>
    <ul class="nav flex-column custom-tabs">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Dashboard</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#application">Card Application</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#apply">Apply for a Card</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#routes">Routes</button></li>
      <li class="nav-item"><a class="nav-link info-tab" href="#">Information</a></li>
      <li class="nav-item"><a class="nav-link" href="#">Feedback</a></li>
      <li class="nav-item"><a class="nav-link" href="logout.php">Sign Out</a></li>
    </ul>
  </div>

  <div class="container mt-5">
    <h2>Welcome, <?= htmlspecialchars($name) ?>!</h2>
    <hr>

    <div class="tab-content mt-3" id="userTabContent">

      <div class="tab-pane fade show active p-3" id="home">
        <div class="card shadow">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6"></div>
              <div class="col-md-6 text-center">
                <h5 class="mb-3">Credit Status</h5>
                <div id="creditMeterContainer">
                  <canvas id="creditMeter" data-credits="<?= $credits ?>" width="200" height="200"></canvas>
                  <div id="creditValue"><?= $credits ?></div>
                </div>
                <h6 class="mt-3">Credits</h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade p-3" id="application">
        <h4>Your Card Application</h4>
        <div class="table-responsive mt-3">
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>CNIC Photo</th>
                <th>Gender</th>
                <th>Age</th>
                <th>User Type</th>
                <th>Created At</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($application): ?>
                <tr>
                  <td><?= htmlspecialchars($application['fullname']) ?></td>
                  <td><?= htmlspecialchars($application['cnic_photo']) ?></td>
                  <td><?= htmlspecialchars($application['gender']) ?></td>
                  <td><?= htmlspecialchars($application['age']) ?></td>
                  <td><?= htmlspecialchars($application['user_role']) ?></td>
                  <td><?= htmlspecialchars($application['created_at']) ?></td>
                  <td>
                    <?php
                      $badgeClass = match($application['status']) {
                        'Accepted' => 'success',
                        'Declined' => 'danger',
                        'Pending' => 'warning',
                        default => 'secondary'
                      };
                    ?>
                    <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($application['status']) ?></span>
                  </td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center">You have not submitted any application.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade p-3" id="apply">
        <h4>Apply for Card</h4>
        <form action="submit_application.php" method="POST" enctype="multipart/form-data" class="mt-3">
          <div class="mb-3">
            <label>Name</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($name) ?>" disabled>
          </div>
          <div class="mb-3">
            <label>CNIC</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($cnic) ?>" disabled>
          </div>
          <div class="mb-3">
            <label>Gender</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($gender) ?>" disabled>
          </div>
          <div class="mb-3">
            <label>Age</label>
            <input type="number" name="age" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>User Type</label>
            <select name="user_type" id="user_type" class="form-control" onchange="toggleStudentFields()" required>
              <option value="">-- Select Type --</option>
              <option value="Normal">Normal</option>
              <option value="Student">Student</option>
            </select>
          </div>

          <div id="student_fields" style="display: none;">
            <div class="mb-3">
              <label>Institution Name</label>
              <input type="text" name="institution_name" class="form-control">
            </div>
            <div class="mb-3">
              <label>Admission Number</label>
              <input type="text" name="admission_number" class="form-control">
            </div>
            <div class="mb-3">
              <label>Year of Education</label>
              <input type="text" name="year_of_education" class="form-control">
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Submit Application</button>
        </form>
      </div>

      <div class="tab-pane fade p-0" id="routes">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-9 p-0">
              <div id="map"></div>
            </div>
            <div class="col-md-3 sidebar">
              <h4 class="text-center">Bus Routes</h4>
              <input id="routeSearch" class="form-control mb-3" placeholder="Search route or stop...">
              <datalist id="searchOptions"></datalist>
              <button id="planTripBtn" class="btn btn-primary w-100 mb-3">Plan a Trip</button>
              <div id="tripPlanner" class="card p-3">
                <label for="startStop" class="form-label mb-1">Start Stop</label>
                <select id="startStop" class="form-select mb-2"></select>
                <label for="endStop" class="form-label mb-1">Destination Stop</label>
                <select id="endStop" class="form-select mb-2"></select>
                <button id="findBusBtn" class="btn btn-success w-100">Find Bus</button>
                <div id="tripResult" class="mt-3"></div>
              </div>
              <ul id="routesMenu" class="list-group"></ul>
              <div id="stopsMenu" class="d-none">
                <button id="backBtn" class="btn btn-secondary mb-3">⬅ Back</button>
                <h5 id="routeTitle"></h5>
                <ul id="stopsList" class="list-group"></ul>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script async src="https://maps.googleapis.com/maps/api/js?key=<?= $API_KEY ?>&callback=initMap"></script>
</body>
</html>
