<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Room Availability Monitoring</title>
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="min-h-screen bg-gray-100 text-gray-800">

<!-- HEADER -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-600 shadow">
    <div class="max-w-7xl mx-auto px-6 py-6 text-white">
        <h1 class="text-3xl font-bold">Room Availability Monitoring</h1>
        <p class="text-sm opacity-90">
            Live RFID Room Status
        </p>
        <a href="../login.php"><div class="text-sm text-600 hover:text-blue-800 hover:underline">Admin Login</div></a>
    </div>
</div>

<!-- CONTENT -->
<div class="max-w-7xl mx-auto px-6 py-8">

    <!-- INFO BAR -->
    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-600">
            Updates every 5 seconds
        </p>
        <span id="clock" class="text-xs text-gray-500"></span>
    </div>

    <!-- ROOM CARDS -->
    <div id="roomGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="col-span-full text-center text-gray-500">
            Loading rooms...
        </div>
    </div>
</div>

<script>
async function loadRooms() {
    const res = await fetch('../api/public_rooms.php');
    const data = await res.json();
    const grid = document.getElementById('roomGrid');
    grid.innerHTML = '';

    if (!data.rooms.length) {
        grid.innerHTML = `
            <p class="col-span-full text-center text-gray-500">
                No rooms available
            </p>`;
        return;
    }

    data.rooms.forEach(r => {
        const occupied = r.occupancy_status === 'occupied';

        let activityText = 'No recent activity';
        if (r.last_user) {
            activityText =
                `${r.last_user} ` +
                (r.event_type === 'tap_out' ? 'tapped OUT' : 'tapped IN');
        }

        grid.innerHTML += `
        <div class="bg-white rounded-xl shadow hover:shadow-md transition p-5 border">

            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        ${r.room_code}
                    </h3>
                    <p class="text-sm text-gray-500">
                        ${r.room_name}
                    </p>
                </div>

                <span class="px-3 py-1 rounded-full text-xs font-semibold
                    ${occupied
                        ? 'bg-red-100 text-red-700'
                        : 'bg-green-100 text-green-700'}">
                    ${occupied ? 'OCCUPIED' : 'AVAILABLE'}
                </span>
            </div>

            <div class="mt-4 text-sm text-gray-700">
                👤 <span class="font-medium">${activityText}</span>
            </div>

            <div class="mt-1 text-xs text-gray-500">
                ⏱ ${r.last_update ?? '—'}
            </div>

        </div>`;
    });


    document.getElementById('clock').textContent =
        'Last refresh: ' + data.time;
}

loadRooms();
setInterval(loadRooms, 5000);
</script>

</body>
</html>
