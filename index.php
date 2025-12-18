<?php
date_default_timezone_set('Asia/Manila');
require 'auth.php';
require 'db_connect.php';

// Only admin & faculty
if (!in_array($_SESSION['role'], ['admin','faculty'])) {
    die("Access denied.");
}

$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

$managedRooms = $conn->query("SELECT COUNT(*) AS total FROM rooms")->fetch_assoc()['total'];

$totalAccess = $conn->query("SELECT COUNT(*) total FROM access_logs")->fetch_assoc()['total'];

$successUnlocks = $conn->query("SELECT COUNT(*) total FROM access_logs WHERE access_result='granted'")->fetch_assoc()['total'];

$accessDenied = $conn->query("SELECT COUNT(*) total FROM access_logs WHERE access_result='denied'")->fetch_assoc()['total'];

$totalSchedules = $conn->query("SELECT COUNT(*) total FROM schedules")->fetch_assoc()['total'];

$lastUpdated = date('Y-m-d H:i:s');

$activeDevices = 1;

$grantedCount = $successUnlocks;
$deniedCount = $accessDenied;

// Bar chart: access per day (last 7 days)
$activityQuery = "
SELECT DATE(timestamp) as day,
SUM(access_result='granted') as granted,
SUM(access_result='denied') as denied
FROM access_logs
WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY day
ORDER BY day ASC
";

$activityData = $conn->query($activityQuery);

$days = [];
$grantedData = [];
$deniedData = [];

while ($row = $activityData->fetch_assoc()) {
    $days[] = date('M j', strtotime($row['day']));
    $grantedData[] = $row['granted'];
    $deniedData[] = $row['denied'];
}

// Today's access count
$todayAccess = $conn->query("
    SELECT COUNT(*) AS total 
    FROM access_logs 
    WHERE DATE(timestamp) = CURDATE()
")->fetch_assoc()['total'];

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


?>
<?php
include "includes/header.php";
?>

<body class="bg-gray-100 font-family-karla flex">

    <?php include "includes/sidebar.php"?>

    <div class="w-full flex flex-col h-screen overflow-y-hidden">
        
        <?php include "includes/userb.php"?>

        <?php include "includes/navbar.php"?>
    
        <div class="w-full overflow-x-hidden border-t flex flex-col">
            <main class="w-full flex-grow p-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    
                    <div>
                        <h1 class="text-2xl font-bold">
                            Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?> !
                        </h1>
                        <p class="text-sm opacity-90 mt-1">
                            RFID Access Control System Dashboard
                        </p>
                    </div>

                    <div class="mt-4 md:mt-0 text-sm text-right">
                        <p class="font-semibold">
                            <?php echo date('l, F j, Y'); ?>
                        </p>
                        <p class="opacity-90">
                            <?php echo date('h:i A'); ?>
                        </p>
                    </div>

                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 p-3 rounded-full">
                    <i class="fas fa-user"></i>
                    </div>
                    <div>
                    <h2 class="text-2xl font-bold"><?php echo $totalUsers; ?></h2>
                        <p class="text-sm text-gray-500">Total Users</p>
                    </div>
                </div>

                <!-- Managed Rooms -->
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                    <i class="fas fa-door-closed"></i>
                    </div>
                    <div>
                    <h2 class="text-2xl font-bold"><?php echo $managedRooms; ?></h2>
                        <p class="text-sm text-gray-500">Managed Rooms</p>
                    </div>
                </div>

                <!-- Active Devices -->
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="bg-blue-100 text-orange-600 p-3 rounded-full">
                    <i class="fas fa-desktop"></i>
                    </div>
                    <div>
                    <h2 class="text-2xl font-bold"><?php echo $activeDevices; ?></h2>
                        <p class="text-sm text-gray-500">Active Devices</p>
                    </div>
                </div>

                <!-- Today’s Access -->
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
                    <i class="fa-solid fa-shield"></i>
                    </div>
                    <div>
                    <h2 class="text-2xl font-bold"><?php echo $todayAccess; ?></h2>
                        <p class="text-sm text-gray-500">Today's Access</p>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ROOM STATUS -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="font-semibold text-lg">Room Status</h3>
                    <span class="text-xs text-gray-400">Live</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Room</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-left">Last Update</th>
                            </tr>
                        </thead>

                        <tbody id="roomStatus">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-gray-400">
                                    Loading…
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <?php
            $activityQuery = "
            SELECT 
                al.access_result,
                al.timestamp,
                u.fullname,
                r.room_name
            FROM access_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN rooms r ON al.room_id = r.id
            ORDER BY al.timestamp DESC
            LIMIT 5
            ";

            $activityResult = $conn->query($activityQuery);
            ?>


            <div class="bg-white rounded-xl shadow">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="font-semibold text-lg">Recent Activity</h3>
                    <span class="text-xs text-green-600 font-semibold">Live</span>
                </div>

                <div id="recentActivity" class="divide-y"></div>
            </div>


        </div>

                <div class="mt-10 mb-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Data Analytics</h2>
                            <p class="text-sm text-gray-500">
                                Comprehensive overview of access control, scheduling, and system analytics
                            </p>
                        </div>
                        <p class="text-sm text-gray-400">
                            ⏱ Last updated: <?php echo $lastUpdated; ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                    <!-- Total Access -->
                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full"><i class="fa-solid fa-key"></i></div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo $totalAccess; ?></h3>
                            <p class="text-sm text-gray-500">Total Access Attempts</p>
                        </div>
                    </div>

                    <!-- Successful Unlocks -->
                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full"><i class="fa-solid fa-unlock"></i></div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo $successUnlocks; ?></h3>
                            <p class="text-sm text-gray-500">Successful Unlocks</p>
                        </div>
                    </div>

                    <!-- Access Denied -->
                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full"><i class="fa-solid fa-lock"></i></div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo $accessDenied; ?></h3>
                            <p class="text-sm text-gray-500">Access Denials</p>
                        </div>
                    </div>

                    <!-- Registered Users -->
                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full"><i class="fa-solid fa-user"></i></div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo $totalUsers; ?></h3>
                            <p class="text-sm text-gray-500">Registered Users</p>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    
                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full"><i class="fa-solid fa-desktop"></i></div>
                        <div>
                        <h3 class="text-xl font-bold"><?php echo $activeDevices; ?></h3>
                        <p class="text-sm text-gray-500">Active Devices</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full"><i class="fa-solid fa-door-closed"></i></div>
                        <div>
                        <h3 class="text-xl font-bold"><?php echo $managedRooms; ?></h3>
                        <p class="text-sm text-gray-500">Total Rooms</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex gap-4 items-center">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full"><i class="fa-solid fa-calendar"></i></div>
                        <div>
                        <h3 class="text-xl font-bold"><?php echo $totalSchedules; ?></h3>
                        <p class="text-sm text-gray-500">Total Schedules</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

                <!-- PIE CHART -->
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Access Status Distribution</h3>
                    </div>
                    <canvas id="accessPieChart"></canvas>
                </div>

                <!-- BAR CHART -->
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">User Access Activity</h3>
                    </div>
                    <canvas id="accessBarChart"></canvas>
                </div>

            </div>




                </div>

            </main>
    
        </div>
        
    </div>

    <?php include "includes/footer.php"?>

    <script>
    /* PIE CHART */
    new Chart(document.getElementById('accessPieChart'), {
        type: 'pie',
        data: {
            labels: ['Granted', 'Denied'],
            datasets: [{
                data: [
                    <?php echo $grantedCount; ?>,
                    <?php echo $deniedCount; ?>
                ],
                backgroundColor: ['#22c55e', '#ef4444']
            }]
        }
    });

    /* BAR CHART */
    new Chart(document.getElementById('accessBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [
                {
                    label: 'Successful Unlocks',
                    data: <?php echo json_encode($grantedData); ?>,
                    backgroundColor: '#22c55e'
                },
                {
                    label: 'Access Denied',
                    data: <?php echo json_encode($deniedData); ?>,
                    backgroundColor: '#ef4444'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>

    <script>
    async function loadDashboardLive() {
        try {
            const res = await fetch('api/dashboard_live.php');
            const data = await res.json();

            // ===== ROOM STATUS =====
            let roomHTML = '';

            if (!data.rooms.length) {
                roomHTML = `
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-center text-gray-400">
                            No rooms found
                        </td>
                    </tr>
                `;
            }

            data.rooms.forEach(room => {
                const occupied = room.occupancy_status === 'occupied';

                const lastUpdate = room.last_update
                    ? new Date(room.last_update).toLocaleString()
                    : '—';

                roomHTML += `
                    <tr class="border-t">
                        <td class="px-6 py-4 font-medium">
                            ${room.room_code} — ${room.room_name}
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                ${occupied 
                                    ? 'bg-red-100 text-red-600' 
                                    : 'bg-green-100 text-green-600'}">
                                ${occupied ? 'Occupied' : 'Available'}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-gray-500 text-sm">
                            ${lastUpdate}
                        </td>
                    </tr>
                `;
            });


            document.getElementById('roomStatus').innerHTML = roomHTML;

        } catch (err) {
            console.error('Live dashboard failed', err);
        }
    }

    // Load immediately
    loadDashboardLive();

    // Refresh every 3 seconds
    setInterval(loadDashboardLive, 1.500);
    </script>

    <script>
    function loadDashboardLive() {
        fetch('api/dashboard_live.php')
            .then(res => res.json())
            .then(data => {

                const activityBox = document.getElementById('recentActivity');
                let activityHTML = '';

                data.logs.forEach(log => {

                    let message = '';
                    let badgeClass = '';
                    let dotClass = '';

                    // ❌ ACCESS DENIED
                    if (log.access_result === 'denied') {
                        message = `
                            <strong>${log.fullname ?? 'Unknown User'}</strong>
                            was <span class="text-red-600 font-semibold">DENIED</span>
                            access to <strong>${log.room_code ?? '—'}</strong>
                        `;
                        badgeClass = 'bg-red-100 text-red-700';
                        dotClass = 'bg-red-500';
                    }

                    // ✅ ACCESS GRANTED
                    else {
                        if (log.event_type === 'tap_in') {
                            message = `
                                <strong>${log.fullname}</strong>
                                tapped <span class="text-green-600 font-semibold">IN</span>
                                to <strong>${log.room_name}</strong>
                            `;
                        } else if (log.event_type === 'tap_out') {
                            message = `
                                <strong>${log.fullname}</strong>
                                tapped <span class="text-blue-600 font-semibold">OUT</span>
                                of <strong>${log.room_name}</strong>
                            `;
                        } else {
                            message = `
                                <strong>${log.fullname}</strong>
                                accessed <strong>${log.room_name}</strong>
                            `;
                        }

                        badgeClass = 'bg-green-100 text-green-700';
                        dotClass = 'bg-green-500';
                    }

                    // 🛑 ADMIN OVERRIDE
                    if (log.access_reason === 'admin_override') {
                        message = `
                            <strong>Admin Override</strong>
                            unlocked <strong>${log.room_name}</strong>
                        `;
                        badgeClass = 'bg-purple-100 text-purple-700';
                        dotClass = 'bg-purple-500';
                    }

                    activityHTML += `
                        <div class="px-6 py-4 flex items-start gap-3">
                            <span class="mt-1 w-2 h-2 rounded-full ${dotClass}"></span>

                            <div class="flex-1">
                                <p class="text-sm">${message}</p>
                                <p class="text-xs text-gray-500">
                                    ${new Date(log.timestamp).toLocaleString()}
                                </p>
                            </div>

                            <span class="px-2 py-1 text-xs rounded ${badgeClass}">
                                ${log.access_result.toUpperCase()}
                            </span>
                        </div>
                    `;
                });

                activityBox.innerHTML = activityHTML || `
                    <div class="px-6 py-6 text-center text-gray-500 text-sm">
                        No recent activity
                    </div>
                `;
            });
    }

    // Load + auto refresh
    loadDashboardLive();
    setInterval(loadDashboardLive, 1500);
    </script>




    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
