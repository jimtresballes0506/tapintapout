<?php
require 'auth.php';
require 'db_connect.php';

// Limit simulator access
if (!in_array($_SESSION['role'], ['admin', 'faculty'])) {
    die("Access denied.");
}

// Load rooms
$roomsRes = $conn->query("SELECT id, room_code, room_name FROM rooms WHERE status='active' ORDER BY room_code");
$rooms = $roomsRes ? $roomsRes->fetch_all(MYSQLI_ASSOC) : [];

// Load RFID tags
$tagsRes = $conn->query("SELECT t.id, t.rfid_uid, t.label, u.fullname, u.username 
                         FROM rfid_tags t 
                         LEFT JOIN users u ON t.user_id = u.id
                         ORDER BY t.created_at DESC LIMIT 200");
$tags = $tagsRes ? $tagsRes->fetch_all(MYSQLI_ASSOC) : [];
?>

<?php
include "includes/header.php";
?>

<body class="bg-gray-100 font-family-karla flex">

    <?php include "includes/sidebar.php"?>

    <div class="relative w-full flex flex-col h-screen overflow-y-hidden">
        
    <?php include "includes/userb.php"?>

    <?php include "includes/navbar.php"?>
    
        <div class="w-full h-screen overflow-x-hidden border-t flex flex-col">
            <main class="w-full flex-grow p-6">
                <h1 class="text-3xl text-black pb-6">Access Logs</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- LEFT PANEL -->
                <div class="bg-white p-4 rounded shadow">
                    <h2 class="font-semibold mb-3">Simulate a Tap</h2>

                    <label class="block text-sm mb-1">Select RFID Tag</label>
                    <select id="tag_select" class="w-full border rounded px-3 py-2 mb-3">
                    <option value="">-- Choose tag or type manually --</option>
                    <?php foreach($tags as $t): ?>
                    <option value="<?= htmlspecialchars($t['rfid_uid']) ?>">
                        <?= htmlspecialchars($t['rfid_uid']) ?>
                        <?= $t['label'] ? " — {$t['label']}" : "" ?>
                        <?= $t['fullname'] ? " — {$t['fullname']}" : "" ?>
                    </option>
                    <?php endforeach; ?>
                    </select>

                    <label class="block text-sm mb-1">RFID UID</label>
                    <input id="rfid_uid" type="text" placeholder="A1B2C3D4"
                        class="w-full border rounded px-3 py-2 mb-3">

                    <button onclick="genUID()" 
                            class="px-3 py-2 bg-gray-200 rounded mb-3">
                    Generate UID
                    </button>

                    <label class="block text-sm mb-1">Room</label>
                    <select id="room_id" class="w-full border rounded px-3 py-2 mb-3">
                    <option value="">-- Select Room --</option>
                    <?php foreach($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>">
                        <?= htmlspecialchars("{$r['room_code']} — {$r['room_name']}") ?>
                        </option>
                    <?php endforeach; ?>
                    </select>

                    <label class="block text-sm mb-1">Event</label>
                    <select id="event_type" class="w-full border rounded px-3 py-2 mb-4">
                    <option value="tap_in">Tap In</option>
                    <option value="tap_out">Tap Out</option>
                    </select>

                    <button onclick="simulateTap()" 
                            class="bg-blue-600 text-white px-4 py-2 rounded">
                    Simulate Tap
                    </button>

                    <div id="simResult" class="mt-4"></div>
                </div>

                <!-- RIGHT PANEL — Recent Logs -->
                <div class="bg-white p-4 rounded shadow">
                    <h2 class="font-semibold mb-3">Recent Logs</h2>
                    <div id="logs_container">
                    <p class="text-gray-500 text-sm">Waiting for simulation…</p>
                    </div>
                </div>

                </div>
            </main>
    
            <footer class="w-full bg-white text-right p-4">
                Built by <a target="_blank" href="https://davidgrzyb.com" class="underline">David Grzyb</a>.
            </footer>
        </div>
        
    </div>

    <!-- AlpineJS -->
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js" integrity="sha256-KzZiKy0DWYsnwMF+X1DvQngQ2/FxF7MF3Ff72XcpuPs=" crossorigin="anonymous"></script>
    <script>
    // Autofill UID when selecting tag
    document.getElementById('tag_select').addEventListener('change', function() {
        document.getElementById('rfid_uid').value = this.value;
    });

    // Generator
    function genUID() {
        const uid = Array.from(crypto.getRandomValues(new Uint8Array(4)))
            .map(b => b.toString(16).padStart(2, "0"))
            .join("")
            .toUpperCase();
        document.getElementById("rfid_uid").value = uid;
    }

    // PLAY BUZZER
    function beep(freq, duration = 150) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.frequency.value = freq;
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();

            gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration / 1000);
            osc.stop(ctx.currentTime + duration / 1000);
        } catch (e) {}
    }

    // MAIN SIMULATION
    async function simulateTap() {
        const rfid = document.getElementById("rfid_uid").value.trim();
        const room = document.getElementById("room_id").value;
        const eventType = document.getElementById("event_type").value;

        if (!rfid || !room) {
            alert("Please enter RFID UID and select a room.");
            return;
        }

        // API URL
        const API_URL = "http://localhost/tapintapoutrfid/api/check_access.php";

        // POST to API
        const formData = new FormData();
        formData.append("rfid", rfid);
        formData.append("door_id", room);
        formData.append("event", eventType);

        const res = await fetch(API_URL, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        // VISUAL FEEDBACK
        const box = document.createElement("div");
        box.className = "sim-box mt-3 " +
            (data.access === "granted"
                ? "bg-green-50 text-green-800"
                : "bg-red-50 text-red-800");

        box.innerHTML = data.access.toUpperCase();

        // BEEP
        if (data.access === "granted") beep(1200, 180);
        else beep(450, 220);

        const resultDiv = document.getElementById("simResult");
        resultDiv.innerHTML = "";
        resultDiv.appendChild(box);

        // Show reason
        const msg = document.createElement("p");
        msg.className = "mt-2 text-sm";
        msg.textContent = "Reason: " + data.reason;
        resultDiv.appendChild(msg);

        // Refresh logs
        loadRecentLogs();
    }

    // Load recent logs
    async function loadRecentLogs() {
        const res = await fetch("logs.php?ajax=1");
        const html = await res.text();
        document.getElementById("logs_container").innerHTML = html;
    }

    // Auto-refresh every 5 seconds
    setInterval(loadRecentLogs, 5000);
    </script>
</body>
</html>
