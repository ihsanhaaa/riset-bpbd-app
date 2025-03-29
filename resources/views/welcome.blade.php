<!DOCTYPE html>
<html lang="id">
<head>
    <title>ABC</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS & JS -->
    <link href="https://unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        #mapid {
            width: 100vw;
            height: 100vh;
        }
        #rainViewerControls {
            display: none;
            position: absolute;
            top: 50px;
            right: 10px;
            z-index: 1000;
            width: 250px;
        }
        .leaflet-control-custom {
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div id="mapid"></div>

    <!-- Control Panel dengan Bootstrap -->
    <div id="rainViewerControls" class="card shadow">
        <div class="card-header text-center bg-primary text-white">Kontrol Radar Cuaca</div>
        <div class="card-body">
            <div class="d-grid gap-2">
                <button class="btn btn-success" onclick="playStop()">Play / Stop</button>
            </div>
            <div class="mt-3">
                <label class="form-label">Opacity</label>
                <input type="range" id="opacity" class="form-range" min="0" max="1" step="0.1" value="0.7" onchange="setOpacity(this.value)">
            </div>
            <div class="mt-3">
                <label class="form-label">Timeline</label>
                <input type="range" id="timeSlider" class="form-range" min="0" max="10" step="1" value="0" onchange="showFrame(this.value)">
            </div>
            <p class="text-center mt-2"><strong id="timestamp">Timestamp</strong></p>
        </div>
        <div class="card-footer text-center">
            <button class="btn btn-danger btn-sm" onclick="toggleRainViewer()">Tutup</button>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var map = L.map('mapid').setView([0.13295, 111.0966], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        var rainViewerLayers = [];
        var rainViewerVisible = false;
        var apiData = {};
        var animationPosition = 0;
        var animationTimer = null;

        // Tambahkan tombol di dalam peta (top-right)
        var toggleButton = L.control({ position: 'topright' });

        toggleButton.onAdd = function(map) {
            var div = L.DomUtil.create('div', 'leaflet-control leaflet-control-custom btn btn-primary');
            div.innerText = "Tampilkan Radar Cuaca";
            div.onclick = function () { toggleRainViewer(); };
            return div;
        };

        toggleButton.addTo(map);

        function fetchRainViewerData() {
            fetch("https://api.rainviewer.com/public/weather-maps.json")
                .then(response => response.json())
                .then(data => {
                    apiData = data;
                    document.getElementById("timeSlider").max = (data.radar.past.length + (data.radar.nowcast?.length || 0)) - 1;
                });
        }

        function toggleRainViewer() {
            if (rainViewerVisible) {
                stop();
                rainViewerLayers.forEach(layer => map.removeLayer(layer));
                rainViewerLayers = [];
                document.getElementById("rainViewerControls").style.display = "none";
                document.querySelector('.leaflet-control-custom').innerText = "Tampilkan Radar Cuaca";
            } else {
                initializeRainViewer();
                document.getElementById("rainViewerControls").style.display = "block";
                document.querySelector('.leaflet-control-custom').innerText = "Tutup Radar Cuaca";
            }
            rainViewerVisible = !rainViewerVisible;
        }

        function initializeRainViewer() {
            if (!apiData.radar) return;
            let frames = apiData.radar.past;
            if (apiData.radar.nowcast) {
                frames = frames.concat(apiData.radar.nowcast);
            }
            frames.forEach(frame => {
                let url = `https://tilecache.rainviewer.com/v2/radar/${frame.path}/256/{z}/{x}/{y}/2/1_1.png`;
                let layer = L.tileLayer(url, { opacity: 0.7, zIndex: 10 });
                rainViewerLayers.push(layer);
            });
            showFrame(animationPosition);
        }

        function showFrame(position) {
            if (position < 0 || position >= rainViewerLayers.length) return;
            rainViewerLayers.forEach(layer => map.removeLayer(layer));
            map.addLayer(rainViewerLayers[position]);

            let time = apiData.radar.past.concat(apiData.radar.nowcast || [])[position].time;
            let label = time > Date.now() / 1000 ? "FORECAST" : "PAST";
            document.getElementById("timestamp").innerText = `${label}: ${new Date(time * 1000).toLocaleString()}`;
        }

        function play() {
            if (animationPosition >= rainViewerLayers.length) {
                animationPosition = 0;
            }
            showFrame(animationPosition++);
            document.getElementById("timeSlider").value = animationPosition;
            animationTimer = setTimeout(play, 500);
        }

        function playStop() {
            if (animationTimer) {
                clearTimeout(animationTimer);
                animationTimer = null;
            } else {
                play();
            }
        }

        function stop() {
            if (animationTimer) {
                clearTimeout(animationTimer);
                animationTimer = null;
            }
        }

        function setOpacity(value) {
            rainViewerLayers.forEach(layer => layer.setOpacity(value));
        }

        fetchRainViewerData();
    </script>
</body>
</html>
