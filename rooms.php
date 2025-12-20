
<?php
require 'auth.php';
require 'db_connect.php';

$totalRooms = $conn->query("
    SELECT COUNT(*) AS total 
    FROM rooms
")->fetch_assoc()['total'];

$availableRooms = $conn->query("
    SELECT COUNT(*) AS total 
    FROM rooms
    WHERE occupancy_status = 'available'
")->fetch_assoc()['total'];

$occupiedRooms = $conn->query("
    SELECT COUNT(*) AS total 
    FROM rooms
    WHERE occupancy_status = 'occupied'
")->fetch_assoc()['total'];

$onlineDevice = 1;

// Only admins can manage rooms - change if you want faculty to manage them too
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

$errors = [];
$success = '';

// Handle POST (add / edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'add';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $room_code = trim($_POST['room_code'] ?? '');
    $room_name = trim($_POST['room_name'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $status = in_array($_POST['status'] ?? 'active', ['active','maintenance','inactive']) ? $_POST['status'] : 'active';

    if ($room_code === '' || $room_name === '') {
        $errors[] = "Room code and name are required.";
    }

    if (empty($errors)) {
        if ($mode === 'add') {
            $stmt = $conn->prepare("INSERT INTO rooms (room_code, room_name, building, floor, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $room_code, $room_name, $building, $floor, $status);
            if ($stmt->execute()) {
                $success = "Room added successfully.";
            } else {
                $errors[] = "Error inserting room: " . $conn->error;
            }
            $stmt->close();
        } elseif ($mode === 'edit' && $id) {
            $stmt = $conn->prepare("UPDATE rooms SET room_code=?, room_name=?, building=?, floor=?, status=? WHERE id=?");
            $stmt->bind_param("sssssi", $room_code, $room_name, $building, $floor, $status, $id);
            if ($stmt->execute()) {
                $success = "Room updated successfully.";
            } else {
                $errors[] = "Error updating room: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Toggle status via GET toggle parameter
if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = $conn->prepare("SELECT status FROM rooms WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($currStatus);
    if ($stmt->fetch()) {
        $newStatus = ($currStatus === 'active') ? 'maintenance' : 'active'; // example toggle
        $stmt->close();

        $u = $conn->prepare("UPDATE rooms SET status=? WHERE id=?");
        $u->bind_param("si", $newStatus, $id);
        $u->execute();
        $u->close();
        $success = "Room status changed to $newStatus.";
    } else {
        $stmt->close();
    }
}

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $check = $conn->prepare("SELECT COUNT(*) FROM access_logs WHERE room_id=?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($cnt);
    $check->fetch();
    $check->close();
    if ($cnt > 0) {
        $errors[] = "Cannot delete room with existing logs.";
    } else {

    $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Room deleted permanently.";
    } else {
        $errors[] = "Error deleting room: " . $conn->error;
    }
    $stmt->close();
    }
}

// Fetch rooms
$result = $conn->query("SELECT * FROM rooms ORDER BY room_code ASC");
$rooms = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
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
                <h1 class="text-3xl text-black pb-6">Room Management</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full">
                        <i class="fa-solid fa-door-closed"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $totalRooms; ?></h2>
                            <p class="text-sm text-gray-500">Total Rooms</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                        <i class="fa-solid fa-door-open"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $availableRooms; ?></h2>
                            <p class="text-sm text-gray-500">Available Rooms</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-blue-100 text-orange-600 p-3 rounded-full">
                        <i class="fa-solid fa-door-closed"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $occupiedRooms; ?></h2>
                            <p class="text-sm text-gray-500">Occupied Rooms</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
                        <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $onlineDevice; ?></h2>
                            <p class="text-sm text-gray-500">Online Devices</p>
                        </div>
                    </div>

                </div>

                <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-2 rounded">
                    <?php foreach ($errors as $e) echo "<div>" . htmlspecialchars($e) . "</div>"; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php
                // Edit mode?
                $editRoom = null;
                if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
                    $editId = (int) $_GET['edit'];
                    foreach ($rooms as $r) {
                        if ((int)$r['id'] === $editId) { $editRoom = $r; break; }
                    }
                }
                $isEdit = $editRoom !== null;
                ?>

                <!-- Add / Edit form -->
                <div class="bg-white shadow rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold mb-3"><?php echo $isEdit ? 'Edit Room' : 'Add New Room'; ?></h2>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="mode" value="<?php echo $isEdit ? 'edit' : 'add'; ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo (int)$editRoom['id']; ?>"><?php endif; ?>

                    <div>
                    <label class="block text-sm mb-1">Room Code</label>
                    <input type="text" name="room_code" class="w-full border rounded px-3 py-2"
                            value="<?php echo htmlspecialchars($editRoom['room_code'] ?? ''); ?>" required>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Room Name</label>
                    <input type="text" name="room_name" class="w-full border rounded px-3 py-2"
                            value="<?php echo htmlspecialchars($editRoom['room_name'] ?? ''); ?>" required>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Building</label>
                    <input type="text" name="building" class="w-full border rounded px-3 py-2"
                            value="<?php echo htmlspecialchars($editRoom['building'] ?? ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Floor</label>
                    <input type="text" name="floor" class="w-full border rounded px-3 py-2"
                            value="<?php echo htmlspecialchars($editRoom['floor'] ?? ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2">
                        <?php
                        $curr = $editRoom['status'] ?? 'active';
                        foreach (['active','maintenance','inactive'] as $s) {
                            $sel = ($curr === $s) ? 'selected' : '';
                            echo "<option value=\"$s\" $sel>" . ucfirst($s) . "</option>";
                        }
                        ?>
                    </select>
                    </div>

                    <div class="md:col-span-2 flex justify-end mt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                        <?php echo $isEdit ? 'Update Room' : 'Add Room'; ?>
                    </button>
                    </div>
                </form>
                </div>

                <!-- Rooms cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($rooms as $r): ?>
                <?php
                $occupied = ($r['occupancy_status'] ?? 'available') === 'occupied';
                $locked   = !$occupied; // assumption: occupied = unlocked
                ?>
                <div class="bg-white rounded-xl shadow p-4 border
                    <?= $occupied ? 'border-red-400' : 'border-green-400' ?>">

                    <!-- HEADER -->
                    <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold text-lg"><?= htmlspecialchars($r['room_code']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($r['room_name']) ?></p>
                        <p class="text-xs text-gray-400 mt-1">
                        <?= htmlspecialchars($r['building']) ?> · Floor <?= htmlspecialchars($r['floor']) ?>
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <!-- Status badge -->
                        <span class="text-xs px-2 py-1 rounded
                        <?= $occupied ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                        <?= $occupied ? 'Occupied' : 'Available' ?>
                        </span>

                        <!-- EDIT BUTTON -->
                        <a href="rooms.php?edit=<?= (int)$r['id'] ?>"
                        class="text-xs bg-blue-600 hover:bg-blue-700
                                text-white px-2 py-1 rounded">
                        Edit
                        </a>
                    </div>
                    </div>

                    <!-- LOCK TOGGLE -->
                    <div class="flex items-center justify-between mt-4">
                    <span class="text-sm font-medium text-gray-700">
                        Lock: <span id="lockText-<?= (int)$r['id'] ?>">Locked</span>
                    </span>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                            class="hidden"
                            onchange="toggleRoomLock(<?= (int)$r['id'] ?>, this)">

                        <!-- Track -->
                        <div id="track-<?= (int)$r['id'] ?>"
                            class="w-11 h-6 bg-gray-300 rounded-full transition-colors duration-300">
                        </div>

                        <!-- Knob -->
                        <div id="knob-<?= (int)$r['id'] ?>"
                            class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full
                                    transition-transform duration-300">
                        </div>
                    </label>
                    </div>

                </div>
                <?php endforeach; ?>
                </div>


            </main>
        </div>
        
    </div>

    <?php include "includes/footer.php"?>

    <script>
    function toggleRoomLock(roomId, checkbox) {
    const track = document.getElementById('track-' + roomId);
    const knob  = document.getElementById('knob-' + roomId);
    const text  = document.getElementById('lockText-' + roomId);

    const isUnlock = checkbox.checked;

    // Animate UI
    if (isUnlock) {
        track.classList.remove('bg-gray-300');
        track.classList.add('bg-green-500');
        knob.style.transform = 'translateX(20px)';
        text.textContent = 'Unlocked';
    } else {
        track.classList.remove('bg-green-500');
        track.classList.add('bg-gray-300');
        knob.style.transform = 'translateX(0)';
        text.textContent = 'Locked';
    }

    // Send command to backend
    fetch('/tapintapoutrfid/api/remote_control.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
        door_id: roomId,
        action: isUnlock ? 'UNLOCK' : 'LOCK'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 'ok') {
        throw new Error('Failed');
        }
    })
    .catch(() => {
        // rollback UI if failed
        checkbox.checked = !isUnlock;
        toggleRoomLock(roomId, checkbox);
        alert('Network error');
    });
    }
    </script>




</body>
</html>
