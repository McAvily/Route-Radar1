function toggleStudentFields() {
    const userType = document.getElementById("user_type").value;
    const studentFields = document.getElementById("student_fields");
    studentFields.style.display = userType === "Student" ? "block" : "none";
}

document.addEventListener("DOMContentLoaded", function () {
    const creditMeter = document.getElementById('creditMeter');
    if (creditMeter) {
        const credits = parseInt(creditMeter.dataset.credits, 10) || 0;
        const ctx = creditMeter.getContext('2d');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Remaining'],
                datasets: [{
                    data: [credits, Math.max(0, 10000 - credits)],
                    backgroundColor: ['#151E3D', '#e0e0e0'],
                    borderWidth: 1
                }]
            },
            options: {
                cutout: '75%',
                responsive: true,
                plugins: {
                    tooltip: { enabled: true },
                    legend: { display: false }
                }
            }
        });

        const creditValueDiv = document.getElementById('creditValue');
        if (creditValueDiv) {
            creditValueDiv.innerText = credits;
        }
    }
});

let map, directionsService;
let renderers = [];
let stopMarkers = [];
let currentRouteIndex = null;

const routes = [
  { name: "Route 1", type: "Standard bus", stops: ["Railway Station, Lahore","Ek Moriya, Lahore","Nawaz Sharif Hospital, Lahore","Kashmiri Gate, Lahore","Lari Adda, Lahore","Azadi Chowk, Lahore","Texali Chowk, Lahore","Bhatti Chowk, Lahore"] },
  { name: "Route 2", type: "Standard bus", stops: ["Samanabad Morr, Lahore","Corporation Chowk, Lahore","Taj Company, Lahore","Sanda, Lahore","Double Sarkan, Lahore","Moon Market, Lahore","Ganda Nala, Lahore","Bhatti Chowk, Lahore"] },
  { name: "Route 3", type: "Standard bus", stops: ["Railway Station, Lahore","Ek Moriya, Lahore","Nawaz Sharif Hospital, Lahore","Kashmiri Gate, Lahore","Lari Adda, Lahore","Azadi Chowk, Lahore","Timber Market, Lahore","Metro, Lahore","Niazi Chowk, Lahore","Shahdara Metro Station, Lahore","Shahdara Lari Adda, Lahore"] },
  { name: "Route 4", type: "Standard bus", stops: ["R.A Bazar, Lahore","Nadeem Chowk, Lahore","Defence Morr, Lahore","Shareef Market, Lahore","Walton, Lahore","Qainchi, Lahore","Ghazi Chowk, Lahore","Chungi Amar Sidhu, Lahore"] },
  { name: "Route 5", type: "Mini bus", stops: ["Shad Bagh Underpass, Lahore","Rajput Park, Lahore","Madina Chowk, Lahore","Lohay Wali Pulley, Lahore","Badami Bagh, Lahore","Lari Adda Gol Chakar, Lahore","Azadi Chowk, Lahore","Taxali Chowk, Lahore","Bhatti Chowk, Lahore"] },
  { name: "Route 6", type: "Mini bus", stops: ["Babu Sabu, Lahore","Niazi Adda, Lahore","City Bus Stand, Lahore","Chowk Yateem Khana, Lahore","Bhala Stop, Lahore","Samanabad Morr, Lahore","Chauburji, Lahore","Riwaz Garden, Lahore","M.A.O College, Lahore","Firdous Cinema, Lahore","Raj Garh Chowk, Lahore"] },
  { name: "Route 7", type: "Standard bus", stops: ["Bagrian, Lahore","Depot Chowk, Lahore","Minhaj University, Lahore","Hamdard Chowk, Lahore","Rehmat Eye Hospital, Lahore","Pindi Stop, Lahore","Peco Morr, Lahore","Kot Lakhpat Railway Station, Lahore","Phatak Mandi, Lahore","Qainchi, Lahore","Ghazi Chowk, Lahore","Chungi Amar Sidhu, Lahore"] },
  { name: "Route 8", type: "Standard bus", stops: ["Doctor Hospital, Lahore","Wafaqi Colony, Lahore","IBA Stop, Lahore","Hailey College, Lahore","Campus Pull, Lahore","Barkat Market, Lahore","Kalma Chowk, Lahore","Qaddafi Stadium, Lahore","Canal, Lahore"] },
  { name: "Route 9", type: "Mini bus", stops: ["Railway Station, Lahore","Haji Camp, Lahore","Shimla Pahari, Lahore","Lahore Zoo, Lahore","Chairing Cross, Lahore","Ganga Ram Hospital, Lahore","Qartaba Chowk, Lahore","Chauburji, Lahore","Sham Nagar, Lahore"] },
  { name: "Route 10", type: "Standard bus", stops: ["Multan Chungi, Lahore","Mustafa Town, Lahore","Karim Block Market, Lahore","PU Examination Center, Lahore","Bhekewal Morr, Lahore","Wahdat Colony, Lahore","Naqsha Stop, Lahore","Canal, Lahore","Ichra, Lahore","Shama, Lahore","Qartaba Chowk, Lahore"] }
];

function initMap() {
  if (!document.getElementById('map')) return;

  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 31.5820, lng: 74.3294 },
    zoom: 12
  });
  directionsService = new google.maps.DirectionsService();

  buildSearchOptions();
  loadRoutesMenu();
  setupTripPlanner();

  const searchInput = document.getElementById("routeSearch");
  searchInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") handleSearchSubmit(searchInput.value.trim());
  });
  searchInput.addEventListener("change", () => handleSearchSubmit(searchInput.value.trim()));
  searchInput.addEventListener("input", () => filterRoutesList(searchInput.value.trim()));
}

function setupTripPlanner() {
  const btn = document.getElementById('planTripBtn');
  const panel = document.getElementById('tripPlanner');
  const startSel = document.getElementById('startStop');
  const endSel = document.getElementById('endStop');
  const resultDiv = document.getElementById('tripResult');

  const allStops = [...new Set(routes.flatMap(r => r.stops))].sort();
  allStops.forEach(s => {
    const o1 = document.createElement('option'); o1.value = s; o1.textContent = s; startSel.appendChild(o1);
    const o2 = document.createElement('option'); o2.value = s; o2.textContent = s; endSel.appendChild(o2);
  });

  btn.onclick = () => {
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  };

  document.getElementById('findBusBtn').onclick = () => {
    const start = startSel.value;
    const end = endSel.value;
    resultDiv.innerHTML = '';

    if (!start || !end || start === end) {
      resultDiv.innerHTML = "<span class='text-danger'>Please select two different stops.</span>";
      return;
    }

    const route = routes.find(r => r.stops.includes(start) && r.stops.includes(end));
    if (!route) {
      resultDiv.innerHTML = `<span class='text-danger'>No direct bus found between <b>${start}</b> and <b>${end}</b>.</span>`;
      return;
    }

    const idxStart = route.stops.indexOf(start);
    const idxEnd = route.stops.indexOf(end);
    const hops = Math.abs(idxEnd - idxStart);
    const speed = (20 + Math.random() * 20).toFixed(1);
    const eta = Math.max(3, (hops * 3) + Math.round(Math.random() * 5));

    resultDiv.innerHTML = `
      <div class="border rounded p-2">
        <div><b>${route.name}</b> <small class="text-muted">(${route.type})</small></div>
        <div>From <b>${start}</b> to <b>${end}</b></div>
        <div>🕒 ETA: <b>${eta} mins</b></div>
        <div>🚍 Current Speed: <b>${speed} km/h</b></div>
        <div class="text-muted mt-1"><small>(Mock data until live bus device is connected)</small></div>
      </div>
    `;

    showRoute(route, routes.indexOf(route), start);
  };
}

function buildSearchOptions() {
  const dl = document.getElementById("searchOptions");
  const seen = new Set();
  document.getElementById("routeSearch").setAttribute("list", "searchOptions");

  routes.forEach(r => {
    const ro = document.createElement("option");
    ro.value = r.name;
    dl.appendChild(ro);
    r.stops.forEach(s => {
      const key = s.toLowerCase();
      if (!seen.has(key)) {
        seen.add(key);
        const so = document.createElement("option");
        so.value = s;
        dl.appendChild(so);
      }
    });
  });
}

function loadRoutesMenu() {
  const menu = document.getElementById("routesMenu");
  menu.innerHTML = "";
  routes.forEach((route, idx) => {
    const li = document.createElement("li");
    li.className = "list-group-item route-item";
    li.innerText = `${route.name} (${route.type})`;
    li.onclick = () => showRoute(route, idx, null);
    menu.appendChild(li);
  });
}

function filterRoutesList(query) {
  const menu = document.getElementById("routesMenu");
  const q = query.toLowerCase();
  Array.from(menu.children).forEach((li, i) => {
    const route = routes[i];
    const matchRoute = route.name.toLowerCase().includes(q);
    const matchStop = route.stops.some(s => s.toLowerCase().includes(q));
    li.style.display = (q === "" || matchRoute || matchStop) ? "" : "none";
  });
}

function handleSearchSubmit(raw) {
  if (!raw) return;
  const q = raw.toLowerCase();

  let idx = routes.findIndex(r => r.name.toLowerCase() === q);
  if (idx !== -1) { showRoute(routes[idx], idx, null); return; }

  for (let i = 0; i < routes.length; i++) {
    const stopIdx = routes[i].stops.findIndex(s => s.toLowerCase() === q);
    if (stopIdx !== -1) { showRoute(routes[i], i, routes[i].stops[stopIdx]); return; }
  }

  idx = routes.findIndex(r => r.name.toLowerCase().includes(q));
  if (idx !== -1) { showRoute(routes[idx], idx, null); return; }

  for (let i = 0; i < routes.length; i++) {
    const s = routes[i].stops.find(st => st.toLowerCase().includes(q));
    if (s) { showRoute(routes[i], i, s); return; }
  }
}

function showRoute(route, idx, focusStopName) {
  clearMap();
  currentRouteIndex = idx;

  const renderer = new google.maps.DirectionsRenderer({
    map,
    polylineOptions: { strokeColor: getColor(idx), strokeWeight: 4 },
    suppressMarkers: true
  });
  renderers.push(renderer);

  const waypoints = route.stops.slice(1, -1).map(stop => ({ location: stop, stopover: true }));

  directionsService.route(
    {
      origin: route.stops[0],
      destination: route.stops[route.stops.length - 1],
      waypoints,
      travelMode: google.maps.TravelMode.DRIVING
    },
    (response, status) => {
      if (status !== "OK") return;
      renderer.setDirections(response);

      const legs = response.routes[0].legs;
      const stopLatLngs = [];
      for (let i = 0; i < legs.length; i++) stopLatLngs.push(legs[i].start_location);
      stopLatLngs.push(legs[legs.length - 1].end_location);

      const iw = new google.maps.InfoWindow();
      route.stops.forEach((stop, i) => {
        const m = new google.maps.Marker({
          position: stopLatLngs[i],
          map,
          title: `${stop} (${route.type})`
        });
        m.addListener("click", () => {
          iw.setContent(`<div><strong>${stop}</strong><br/><small>${route.name} • ${route.type}</small></div>`);
          iw.open(map, m);
        });
        stopMarkers.push(m);
      });

      if (focusStopName) {
        const exactIdx = route.stops.findIndex(s => s.toLowerCase() === focusStopName.toLowerCase());
        const idxStop = exactIdx !== -1 ? exactIdx : route.stops.findIndex(s => s.toLowerCase().includes((focusStopName||"").toLowerCase()));
        if (idxStop !== -1) {
          const targetMarker = stopMarkers[idxStop];
          map.setZoom(15);
          map.panTo(targetMarker.getPosition());
          targetMarker.setAnimation(google.maps.Animation.BOUNCE);
          setTimeout(() => targetMarker.setAnimation(null), 2000);
        }
      }
    }
  );

  document.getElementById("routesMenu").classList.add("d-none");
  document.getElementById("stopsMenu").classList.remove("d-none");
  document.getElementById("routeTitle").innerText = `${route.name} - Stops`;

  const stopsList = document.getElementById("stopsList");
  stopsList.innerHTML = "";
  route.stops.forEach(stop => {
    const li = document.createElement("li");
    li.className = "list-group-item";
    li.textContent = stop;
    li.onclick = () => focusStop(stop);
    stopsList.appendChild(li);
  });

  document.getElementById("backBtn").onclick = () => {
    clearMap();
    currentRouteIndex = null;
    document.getElementById("routesMenu").classList.remove("d-none");
    document.getElementById("stopsMenu").classList.add("d-none");
    document.getElementById("routeSearch").value = "";
    filterRoutesList("");
    map.setCenter({ lat: 31.5820, lng: 74.3294 });
    map.setZoom(12);
  };
}

function focusStop(stopName) {
  if (currentRouteIndex === null) return;
  const route = routes[currentRouteIndex];
  const idxStop = route.stops.findIndex(s => s.toLowerCase() === stopName.toLowerCase());
  if (idxStop === -1 || !stopMarkers[idxStop]) return;

  const m = stopMarkers[idxStop];
  map.setZoom(15);
  map.panTo(m.getPosition());
  m.setAnimation(google.maps.Animation.BOUNCE);
  setTimeout(() => m.setAnimation(null), 2000);
}

function clearMap() {
  renderers.forEach(r => r.setMap(null));
  renderers = [];
  stopMarkers.forEach(m => m.setMap(null));
  stopMarkers = [];
}

function getColor(index) {
  const colors = ["#007bff","#28a745","#dc3545","#fd7e14","#6f42c1","#795548","#e83e8c","#17a2b8","#6610f2","#000000"];
  return colors[index % colors.length];
}

document.addEventListener('DOMContentLoaded', () => {
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const mobileSideMenu = document.getElementById('mobileSideMenu');
  const closeMenu = document.getElementById('closeMenu');

  mobileMenuBtn.addEventListener('click', () => {
    mobileSideMenu.classList.add('open');
  });

  closeMenu.addEventListener('click', () => {
    mobileSideMenu.classList.remove('open');
  });

  window.addEventListener('click', (e) => {
    if (!mobileSideMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
      mobileSideMenu.classList.remove('open');
    }
  });
});
