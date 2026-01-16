<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

require_once "database.php";
$API_KEY = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['app_id'], $_POST['action'])) {
        $stmt = $conn->prepare("UPDATE card_applications SET status=? WHERE id=?");
        $status = ($_POST['action'] === 'accept') ? 'Accepted' : 'Declined';
        $app_id = $_POST['app_id'];

        $stmt->bind_param("si", $status, $app_id);

        $stmt->execute();
        $stmt->close();
        header("Location: admin.php");
        exit();
    }

    if (isset($_POST['credit_user_id'], $_POST['top_up_amount'])) {
        $stmt = $conn->prepare(
            "UPDATE card_applications SET credits = credits + ? WHERE id = ?"
        );
        $stmt->bind_param("di", $_POST['top_up_amount'], $_POST['credit_user_id']);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php");
        exit();
    }
}

$search = "";
if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $like = "%{$search}%";
    $stmt = $conn->prepare(
        "SELECT id, user_id, fullname, cnic_photo, gender, age, user_role,
                created_at, status, credits, rfid_uid
         FROM card_applications
         WHERE fullname LIKE ? OR CAST(user_id AS CHAR) LIKE ?
         ORDER BY created_at DESC"
    );
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare(
        "SELECT id, user_id, fullname, cnic_photo, gender, age, user_role,
                created_at, status, credits, rfid_uid
         FROM card_applications
         ORDER BY created_at DESC"
    );
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <span class="navbar-brand">Route Radar</span>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item">
        <a class="nav-link" href="logout.php">Sign Out</a>
      </li>
    </ul>
  </div>
</nav>

<div class="container mt-5">
  <h2>Welcome, <?= htmlspecialchars($_SESSION['user']) ?> (Admin)</h2>
  <hr>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#applications">
        Card Applications
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mapTab" id="mapTabBtn">
        Map View
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mapTab" id="mapTabBtn">
        Drivers
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mapTab" id="mapTabBtn">
        Fleet
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <div class="tab-pane fade show active" id="applications">

      <form method="GET" class="mb-3">
        <div class="input-group">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                 class="form-control" placeholder="Search by name or user ID">
          <button class="btn btn-outline-primary">Search</button>
          <a href="admin.php" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
          <thead class="table-primary">
            <tr>
              <th>User ID</th><th>Name</th><th>CNIC</th><th>Gender</th><th>Age</th>
              <th>User Role</th><th>Status</th><th>Credits</th><th>RFID</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['user_id']) ?></td>
              <td><?= htmlspecialchars($row['fullname']) ?></td>
              <td><?= htmlspecialchars($row['cnic_photo']) ?></td>
              <td><?= htmlspecialchars($row['gender']) ?></td>
              <td><?= htmlspecialchars($row['age']) ?></td>
              <td><?= htmlspecialchars($row['user_role']) ?></td>
              <td>
                <span class="badge bg-<?= $row['status']==='Accepted'?'success':($row['status']==='Declined'?'danger':'warning') ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td>
                <?= htmlspecialchars($row['credits']) ?>
                <form method="POST" class="d-flex mt-1">
                  <input type="hidden" name="credit_user_id" value="<?= $row['id'] ?>">
                  <input type="number" name="top_up_amount" step="0.01"
                         class="form-control form-control-sm me-1">
                  <button class="btn btn-sm btn-outline-success">Top Up</button>
                </form>
              </td>
              <td><strong><?= $row['rfid_uid'] ?? 'Not Assigned' ?></strong></td>
              <td>
                <button class="btn btn-sm btn-primary"
                        onclick="startAssign(<?= $row['id'] ?>, this)">Assign</button>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="app_id" value="<?= $row['id'] ?>">
                  <button name="action" value="accept"
                          class="btn btn-sm btn-success"
                          <?= $row['status']==='Accepted'?'disabled':'' ?>>Accept</button>
                </form>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="app_id" value="<?= $row['id'] ?>">
                  <button name="action" value="decline"
                          class="btn btn-sm btn-danger"
                          <?= $row['status']==='Declined'?'disabled':'' ?>>Decline</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="mapTab">
      <div class="row">
        <div class="col-md-4">
          <div class="accordion" id="routesAccordion"></div>
        </div>
        <div class="col-md-8">
          <div id="map" style="height:520px;"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
let assignPoller=null;
function startAssign(userId,btn){
  document.querySelectorAll("button.btn-primary").forEach(b=>{
    b.disabled=true; if(b!==btn) b.innerText="Busy";
  });
  btn.innerText="Waiting for card...";
  fetch("api/assign_state.php",{method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body:"user_id="+userId})
  .then(r=>r.json()).then(d=>d.success?startPolling():location.reload());
}
function startPolling(){
  assignPoller=setInterval(()=>{
    fetch("api/assign_state.php").then(r=>r.json()).then(d=>{
      if(!d.active){clearInterval(assignPoller);location.reload();}
    });
  },1500);
}
</script>

<script>
let map, markers=[], renderer, info;
let liveBusMarker = null;
let livePoller = null;
let openRouteId = null;

const ROUTE4_START = { lat: 31.481822, lng: 74.301509 };
const ROUTE4_DEST  = { lat: 31.481111, lng: 74.303056 };

const route4StopCoords = [
  { name:"FAST University Lahore", lat:31.481822, lng:74.301509 },
  { name:"Canal Road", lat:31.4827, lng:74.3020 },
  { name:"Model Town Link Road", lat:31.4836, lng:74.3027 },
  { name:"Maulana Shaukat Ali Road", lat:31.481111, lng:74.303056 }
];

const routes = {
  route1:{
    info:{speed:"38 km/h",eta:"6 min",passengers:42},
    stops:["Railway Station","Ek Moriya","Nawaz Sharif Hospital","Kashmiri Gate","Lari Adda","Azadi Chowk","Texali Chowk","Bhatti Chowk"]
  },
  route2:{
    info:{speed:"32 km/h",eta:"8 min",passengers:36},
    stops:["Samanabad Morr","Corporation Chowk","Taj Company","Sanda","Double Sarkan","Moon Market","Ganda Nala","Bhatti Chowk"]
  },
  route3:{
    info:{speed:"41 km/h",eta:"5 min",passengers:51},
    stops:["Railway Station","Ek Moriya","Nawaz Sharif Hospital","Kashmiri Gate","Lari Adda","Azadi Chowk","Timber Market","METRO","Niazi Chowk","Shahdara Metro Station","Shahdara Lari Adda"]
  },
  route4:{
    info:{speed:"LIVE",eta:"LIVE",passengers:"1/30",driver:"Ahmed"},
    stops:["FAST University Lahore","Canal Road","Model Town Link Road","Maulana Shaukat Ali Road"]
  }
};

function initMap(){
  map=new google.maps.Map(document.getElementById("map"),{
    center:{lat:31.55,lng:74.34},zoom:12
  });
  info=new google.maps.InfoWindow();
  buildRouteUI();
}

function buildRouteUI(){
  const acc=document.getElementById("routesAccordion");
  const open = acc.querySelector(".accordion-collapse.show");
  openRouteId = open ? open.id : null;

  acc.innerHTML="";
  let i=1;

  for(const k in routes){
    const isOpen = (k === openRouteId);
    acc.innerHTML+=`
    <div class="accordion-item">
      <h2 class="accordion-header">
        <button class="accordion-button ${isOpen?'':'collapsed'}"
                data-bs-toggle="collapse"
                data-bs-target="#${k}">
          Route ${i++}
        </button>
      </h2>
      <div id="${k}" class="accordion-collapse collapse ${isOpen?'show':''}">
        <div class="accordion-body">
          <div class="small mb-2">
            Speed: <b>${routes[k].info.speed}</b><br>
            ETA (next stop): <b>${routes[k].info.eta}</b><br>
            Passengers: <b>${routes[k].info.passengers}</b><br>
            ${routes[k].info.driver ? `Driver: <b>${routes[k].info.driver}</b>` : ``}
          </div>

          <ul class="list-group mb-2">
            ${routes[k].stops.map(s=>`
              <li class="list-group-item list-group-item-action"
                  onclick="focusStop('${s}')">${s}</li>
            `).join("")}
          </ul>

          <button class="btn btn-sm btn-outline-primary"
                  onclick="drawRoute('${k}')">Show Route on Map</button>
        </div>
      </div>
    </div>`;
  }
}

function clearMap(){
  markers.forEach(m=>m.setMap(null));
  markers=[];
  if(renderer) renderer.setMap(null);
  if(livePoller){ clearInterval(livePoller); livePoller=null; }
  if(liveBusMarker){ liveBusMarker.setMap(null); liveBusMarker=null; }
}

function distanceKm(a,b){
  const R=6371;
  const dLat=(b.lat-a.lat)*Math.PI/180;
  const dLng=(b.lng-a.lng)*Math.PI/180;
  const x=Math.sin(dLat/2)**2+
    Math.cos(a.lat*Math.PI/180)*Math.cos(b.lat*Math.PI/180)*
    Math.sin(dLng/2)**2;
  return R*(2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x)));
}

function getNextStop(pos){
  let closest=null, minDist=Infinity;
  for(const s of route4StopCoords){
    const d=distanceKm(pos,s);
    if(d<minDist){
      minDist=d;
      closest=s;
    }
  }
  return closest;
}

function drawRoute(k){
  clearMap();
  if(k==="route4"){
    const ds=new google.maps.DirectionsService();
    ds.route({
      origin:ROUTE4_START,
      destination:ROUTE4_DEST,
      travelMode:"DRIVING"
    },(res,st)=>{
      if(st==="OK"){
        renderer=new google.maps.DirectionsRenderer({
          map,
          polylineOptions:{strokeColor:"#0d6efd",strokeWeight:5}
        });
        renderer.setDirections(res);
        startLiveTracking();
      }
    });
  }
}

function startLiveTracking(){
  livePoller=setInterval(()=>{
    fetch("api/route4_live.json?ts="+Date.now())
    .then(r=>r.json())
    .then(d=>{
      if(!d.lat||!d.lng||!d.speed) return;

      const pos={lat:+d.lat,lng:+d.lng};

      if(!liveBusMarker){
        liveBusMarker=new google.maps.Marker({
          map,
          position:pos,
          icon:{
            path:google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            scale:6,
            fillColor:"#0d6efd",
            fillOpacity:1,
            strokeWeight:2
          }
        });
        map.setCenter(pos);
        map.setZoom(15);
      } else {
        liveBusMarker.setPosition(pos);
      }

      const speedKmh=(+d.speed)*3.6;
      routes.route4.info.speed=speedKmh.toFixed(2)+" km/h";

      const nextStop=getNextStop(pos);
      const dist=distanceKm(pos,nextStop);
      const etaMin=speedKmh>0?(dist/speedKmh*60):"—";
      routes.route4.info.eta=Math.ceil(etaMin)+" min";

      buildRouteUI();
    });
  },2000);
}

function focusStop(name){
  const g=new google.maps.Geocoder();
  g.geocode({address:name+", Lahore"},(r,s)=>{
    if(s==="OK"){
      map.setCenter(r[0].geometry.location);
      map.setZoom(15);
      info.setContent(`<b>${name}</b>`);
      info.setPosition(r[0].geometry.location);
      info.open(map);
    }
  });
}

document.getElementById("mapTabBtn")
  .addEventListener("shown.bs.tab",()=>google.maps.event.trigger(map,"resize"));
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= $API_KEY ?>&callback=initMap" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
