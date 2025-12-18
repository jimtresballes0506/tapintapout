<?php
require 'auth.php';
require 'db_connect.php';

if (isset($_GET['ajax'])) {
    $recentRes = $conn->query("
        SELECT 
            al.id,
            al.timestamp,
            al.rfid_uid,
            al.event_type,
            al.access_result,
            al.access_reason,
            u.fullname,
            u.username,
            r.room_code,
            r.room_name
        FROM access_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN rooms r ON al.room_id = r.id
        ORDER BY al.timestamp DESC
        LIMIT 10
    ");

    $logs = $recentRes ? $recentRes->fetch_all(MYSQLI_ASSOC) : [];

    echo "<table class='min-w-full text-sm'>";
    echo "<tr><th>Time</th><th>UID</th><th>User</th><th>Room</th><th>Event</th><th>Result</th></tr>";
    foreach ($logs as $row) {
        echo "<tr class='border-b'>
                <td>{$row['timestamp']}</td>
                <td>{$row['rfid_uid']}</td>
                <td>".($row['fullname'] ?: '—')."</td>
                <td>{$row['room_code']} - {$row['room_name']}</td>
                <td>{$row['event_type']}</td>
                <td>{$row['access_result']}</td>
              </tr>";
    }
    echo "</table>";
    exit;
}

// Optional: restrict to admin/faculty if desired
// if (!in_array($_SESSION['role'], ['admin','faculty'])) { die("Access denied."); }

// Get filter inputs (GET)
$filter_user   = trim($_GET['filter_user'] ?? '');
$filter_room   = $_GET['filter_room'] ?? 'all';
$filter_result = $_GET['filter_result'] ?? 'all';
$from_date     = trim($_GET['from_date'] ?? '');
$to_date       = trim($_GET['to_date'] ?? '');

// Build WHERE clauses safely using real_escape_string
$where = [];
if ($filter_user !== '') {
    $fu = $conn->real_escape_string($filter_user);
    $where[] = "(u.fullname LIKE '%$fu%' OR u.username LIKE '%$fu%' OR al.rfid_uid LIKE '%$fu%')";
}
if ($filter_room !== 'all' && ctype_digit($filter_room)) {
    $fr = (int)$filter_room;
    $where[] = "al.room_id = $fr";
}
if ($filter_result !== 'all' && in_array($filter_result, ['granted','denied'])) {
    $frs = $conn->real_escape_string($filter_result);
    $where[] = "al.access_result = '$frs'";
}
if ($from_date !== '') {
    $fd = $conn->real_escape_string($from_date);
    $where[] = "DATE(al.timestamp) >= '$fd'";
}
if ($to_date !== '') {
    $td = $conn->real_escape_string($to_date);
    $where[] = "DATE(al.timestamp) <= '$td'";
}

$where_sql = '';
if (count($where) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Fetch rooms and users for filters
$roomsRes = $conn->query("SELECT id, room_code, room_name FROM rooms ORDER BY room_code");
$rooms = $roomsRes ? $roomsRes->fetch_all(MYSQLI_ASSOC) : [];

$usersRes = $conn->query("SELECT id, fullname, username FROM users ORDER BY fullname");
$users = $usersRes ? $usersRes->fetch_all(MYSQLI_ASSOC) : [];

// Build main query (join user & room)
$sql = "SELECT al.id, al.rfid_uid, al.user_id, u.fullname, u.username, al.room_id, r.room_code, r.room_name,
               al.event_type, al.access_result, al.access_reason, al.timestamp
        FROM access_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN rooms r ON al.room_id = r.id
        $where_sql
        ORDER BY al.timestamp DESC
        LIMIT 1000"; // limit to 1000 for safety; adjust as needed

$res = $conn->query($sql);
$logs = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=access_logs.csv');

    $out = fopen('php://output', 'w');
    // header
    fputcsv($out, ['ID','RFID UID','User ID','User','Username','Room ID','Room','Event','Result','Timestamp']);

    foreach ($logs as $row) {
        fputcsv($out, [
            $row['id'],
            $row['rfid_uid'],
            $row['user_id'],
            $row['fullname'],
            $row['username'],
            $row['room_id'],
            $row['room_code'] ? ($row['room_code'] . ' - ' . $row['room_name']) : '',
            $row['event_type'],
            $row['access_result'],
            $row['timestamp']
        ]);
    }
    fclose($out);
    exit;
}
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

                <!-- Filters -->
                <form method="GET" class="mb-4 bg-white p-4 rounded shadow flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs mb-1">Search (user/uid/label)</label>
                    <input type="text" name="filter_user" value="<?php echo htmlspecialchars($filter_user); ?>"
                        class="border rounded px-3 py-2 w-56" placeholder="e.g. Juan or A1B2C3">
                </div>

                <div>
                    <label class="block text-xs mb-1">Room</label>
                    <select name="filter_room" class="border rounded px-3 py-2">
                    <option value="all">All rooms</option>
                    <?php foreach ($rooms as $r): $sel = ($filter_room == $r['id']) ? 'selected' : ''; ?>
                        <option value="<?php echo (int)$r['id']; ?>" <?php echo $sel; ?>>
                        <?php echo htmlspecialchars($r['room_code'] . ' — ' . $r['room_name']); ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-1">Result</label>
                    <select name="filter_result" class="border rounded px-3 py-2">
                    <option value="all">All</option>
                    <option value="granted" <?php if ($filter_result === 'granted') echo 'selected'; ?>>Granted</option>
                    <option value="denied" <?php if ($filter_result === 'denied') echo 'selected'; ?>>Denied</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-1">From</label>
                    <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs mb-1">To</label>
                    <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="border rounded px-3 py-2">
                </div>

                <div class="flex items-center space-x-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
                    <a href="logs.php" class="px-4 py-2 border rounded text-sm">Reset</a>
                    <!-- Export preserves current GET query -->
                    <?php
                    // build current query for export
                    $query = $_GET;
                    $query['export'] = 'csv';
                    $export_url = 'logs.php?' . http_build_query($query);
                    ?>
                    <a href="<?php echo $export_url; ?>" class="px-4 py-2 bg-gray-700 text-white rounded text-sm">Export CSV</a>
                </div>
                </form>

                <!-- Table -->
                <div class="bg-white rounded shadow p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Time</th>
                        <th class="px-3 py-2 text-left">RFID UID</th>
                        <th class="px-3 py-2 text-left">User</th>
                        <th class="px-3 py-2 text-left">Room</th>
                        <th class="px-3 py-2 text-left">Event</th>
                        <th class="px-3 py-2 text-left">Result</th>
                        <th class="px-3 py-2 text-left">Reason</th>
                        </tr>
                    </thead>
                    <?php if (empty($logs)): ?>
                    <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">
                        No logs found for selected filters.
                    </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $row): 
                        $reason = strtolower(trim($row['access_reason'] ?? 'normal'));
                        echo '<!-- ' . json_encode(array_keys($row)) . ' -->';

                    ?>
                    <tr class="border-b hover:bg-gray-50">
                    <td class="px-3 py-2"><?php echo (int)$row['id']; ?></td>

                    <td class="px-3 py-2 text-xs text-gray-600">
                        <?php echo htmlspecialchars($row['timestamp']); ?>
                    </td>

                    <td class="px-3 py-2 font-mono">
                        <?php echo htmlspecialchars($row['rfid_uid']); ?>
                    </td>

                    <td class="px-3 py-2">
                        <?php echo $row['fullname'] 
                        ? htmlspecialchars($row['fullname'] . " ({$row['username']})") 
                        : '—'; ?>
                    </td>

                    <td class="px-3 py-2">
                        <?php echo $row['room_code'] 
                        ? htmlspecialchars($row['room_code'] . ' — ' . $row['room_name']) 
                        : '—'; ?>
                    </td>

                    <td class="px-3 py-2">
                        <?php echo htmlspecialchars($row['event_type']); ?>
                    </td>

                    <!-- RESULT -->
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 rounded text-xs
                        <?php echo $row['access_result'] === 'granted'
                            ? 'bg-green-100 text-green-700'
                            : 'bg-red-100 text-red-700'; ?>">
                        <?php echo htmlspecialchars(ucfirst($row['access_result'])); ?>
                        </span>
                    </td>

                    <!-- REASON -->
                    <td class="px-3 py-2">
                    <?php if ($reason === 'admin_override'): ?>
                        <span class="px-2 py-1 text-xs rounded badge-override">
                            Override
                        </span>
                    <?php elseif ($reason === 'outside_schedule'): ?>
                        <span class="px-2 py-1 text-xs rounded badge-schedule">
                            Not Scheduled
                        </span>
                    <?php elseif ($reason === 'unknown_tag'): ?>
                        <span class="px-2 py-1 text-xs rounded badge-default">
                            Unknown Tag
                        </span>
                    <?php elseif ($reason === 'user_inactive'): ?>
                        <span class="px-2 py-1 text-xs rounded badge-denied">
                            User Inactive
                        </span>
                    <?php else: ?>
                        <span class="px-2 py-1 text-xs rounded badge-default">
                            Normal
                        </span>
                    <?php endif; ?>

                    </td>

                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    </table>
                </div>
                </div>
            </main>
        </div>
        
    </div>

    <?php include "includes/footer.php"?>
</body>
</html>
