<?php
require 'auth.php';
require 'db_connect.php';

// Only admin & faculty
if (!in_array($_SESSION['role'], ['admin','faculty'])) {
    die("Access denied.");
}

// Total taps today
$totalToday = $conn->query("
    SELECT COUNT(*) AS total 
    FROM access_logs 
    WHERE DATE(timestamp) = CURDATE()
")->fetch_assoc()['total'] ?? 0;

// Granted vs denied
$granted = 0;
$denied = 0;
$res = $conn->query("
    SELECT access_result, COUNT(*) AS total 
    FROM access_logs 
    WHERE DATE(timestamp) = CURDATE()
    GROUP BY access_result
");
while ($row = $res->fetch_assoc()) {
    if ($row['access_result'] === 'granted') $granted = $row['total'];
    if ($row['access_result'] === 'denied') $denied = $row['total'];
}

// Most used room
$mostRoom = $conn->query("
    SELECT r.room_code, r.room_name, COUNT(*) AS total
    FROM access_logs al
    JOIN rooms r ON al.room_id = r.id
    WHERE DATE(al.timestamp) = CURDATE()
    GROUP BY al.room_id
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

// Peak hour
$peak = $conn->query("
    SELECT HOUR(timestamp) AS hour, COUNT(*) AS total
    FROM access_logs
    WHERE DATE(timestamp) = CURDATE()
    GROUP BY hour
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

// Room usage list
$roomUsage = $conn->query("
    SELECT r.room_code, r.room_name, COUNT(*) AS total
    FROM access_logs al
    JOIN rooms r ON al.room_id = r.id
    WHERE DATE(al.timestamp) = CURDATE()
    GROUP BY al.room_id
    ORDER BY total DESC
");
?>
<!doctype html>
<html>
<head>
<title>TapInTapOut | Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="flex min-h-screen">

<!-- SIDEBAR -->
<aside class="w-64 bg-blue-800 text-white p-4">
  <h2 class="text-xl font-bold mb-6">TapInTapOut</h2>
  <nav class="space-y-2 text-sm">
    <a href="dashboard.php" class="block px-3 py-2 rounded bg-blue-900">Dashboard</a>
    <a href="users.php" class="block px-3 py-2 rounded hover:bg-blue-700">Users</a>
    <a href="rooms.php" class="block px-3 py-2 rounded hover:bg-blue-700">Rooms</a>
    <a href="schedules.php" class="block px-3 py-2 rounded hover:bg-blue-700">Schedules</a>
    <a href="rfid_tags.php" class="block px-3 py-2 rounded hover:bg-blue-700">RFID Tags</a>
    <a href="logs.php" class="block px-3 py-2 rounded hover:bg-blue-700">Access Logs</a>
    <a href="simulator.php" class="block px-3 py-2 rounded hover:bg-blue-700">Simulator</a>
    <a href="logout.php" class="block px-3 py-2 rounded hover:bg-blue-700 mt-6">Logout</a>
  </nav>
</aside>

<!-- MAIN -->
<main class="flex-1 p-6">

<h1 class="text-2xl font-bold mb-6">Dashboard Overview</h1>

<!-- METRICS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

<div class="bg-white p-4 rounded shadow">
  <p class="text-sm text-gray-500">Total Taps Today</p>
  <p class="text-3xl font-bold"><?= $totalToday ?></p>
</div>

<div class="bg-white p-4 rounded shadow">
  <p class="text-sm text-gray-500">Granted</p>
  <p class="text-3xl font-bold text-green-600"><?= $granted ?></p>
</div>

<div class="bg-white p-4 rounded shadow">
  <p class="text-sm text-gray-500">Denied</p>
  <p class="text-3xl font-bold text-red-600"><?= $denied ?></p>
</div>

<div class="bg-white p-4 rounded shadow">
  <p class="text-sm text-gray-500">Peak Hour</p>
  <p class="text-2xl font-bold">
    <?= $peak ? $peak['hour'] . ":00" : "—" ?>
  </p>
</div>

</div>

<!-- MOST USED ROOM -->
<div class="bg-white p-4 rounded shadow mb-6">
<h2 class="font-semibold mb-2">Most Used Room Today</h2>
<?php if ($mostRoom): ?>
<p class="text-lg font-bold">
  <?= $mostRoom['room_code'] ?> — <?= $mostRoom['room_name'] ?>
</p>
<p class="text-sm text-gray-500"><?= $mostRoom['total'] ?> taps</p>
<?php else: ?>
<p class="text-gray-500">No data today</p>
<?php endif; ?>
</div>

<!-- ROOM USAGE TABLE -->
<div class="bg-white p-4 rounded shadow">
<h2 class="font-semibold mb-3">Room Usage Today</h2>

<table class="w-full text-sm">
<thead class="border-b">
<tr>
<th class="text-left py-2">Room</th>
<th class="text-left py-2">Total Taps</th>
</tr>
</thead>
<tbody>
<?php while ($r = $roomUsage->fetch_assoc()): ?>
<tr class="border-b">
<td class="py-2"><?= $r['room_code'] ?> — <?= $r['room_name'] ?></td>
<td class="py-2"><?= $r['total'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>

</main>
</div>
</body>
</html>
