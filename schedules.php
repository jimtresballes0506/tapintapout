<?php
require 'auth.php';
require 'db_connect.php';

$totalSched = $conn->query("
    SELECT COUNT(*) AS total 
    FROM schedules
")->fetch_assoc()['total'];

$courseSessions = 0;

$totalFaculty = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'faculty'
    AND status = 'active'
")->fetch_assoc()['total'];

$occupiedRooms = $conn->query("
    SELECT COUNT(*) AS total 
    FROM rooms
    WHERE occupancy_status = 'occupied'
")->fetch_assoc()['total'];

// Only admins (change if needed)
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

$errors = [];
$success = '';

// Fetch rooms and faculty lists for selects
$roomsRes = $conn->query("SELECT id, room_code, room_name FROM rooms WHERE status='active' ORDER BY room_code");
$rooms = $roomsRes ? $roomsRes->fetch_all(MYSQLI_ASSOC) : [];

$facRes = $conn->query("SELECT id, fullname FROM users WHERE role='faculty' AND status='active' ORDER BY fullname");
$faculties = $facRes ? $facRes->fetch_all(MYSQLI_ASSOC) : [];

// Handle POST add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'add';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

    $room_id = (int) ($_POST['room_id'] ?? 0);
    $faculty_id = (int) ($_POST['faculty_id'] ?? 0);
    $day = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    // Basic validation
    if ($room_id <= 0) $errors[] = "Please select a room.";
    if ($faculty_id <= 0) $errors[] = "Please select a faculty member.";
    if (!in_array($day, ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])) $errors[] = "Invalid day.";
    if ($start_time === '' || $end_time === '') $errors[] = "Start and end times are required.";
    if ($start_time >= $end_time) $errors[] = "Start time must be before end time.";

    // OPTIONAL: check overlapping schedules for same room and day
    if (empty($errors)) {
        $overlapCheckSql = "
            SELECT COUNT(*) FROM schedules
            WHERE room_id = ? AND day_of_week = ? 
              AND NOT (end_time <= ? OR start_time >= ?)";
        $stmtCheck = $conn->prepare($overlapCheckSql);
        // For edit, exclude current id
        if ($mode === 'edit' && $id) {
            $overlapCheckSql .= " AND id != ?";
            $stmtCheck = $conn->prepare("
                SELECT COUNT(*) FROM schedules
                WHERE room_id = ? AND day_of_week = ? 
                  AND NOT (end_time <= ? OR start_time >= ?)
                  AND id != ?");
            $stmtCheck->bind_param("isssi", $room_id, $day, $start_time, $end_time, $id);
        } else {
            $stmtCheck->bind_param("isss", $room_id, $day, $start_time, $end_time);
        }
        $stmtCheck->execute();
        $stmtCheck->bind_result($overlapCnt);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($overlapCnt > 0) {
            $errors[] = "Schedule overlaps with an existing schedule in this room on $day.";
        }
    }

    if (empty($errors)) {
        if ($mode === 'add') {
            $ins = $conn->prepare("INSERT INTO schedules
                (room_id, faculty_id, subject_code, subject_name, day_of_week, start_time, end_time, academic_year, semester)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iisssssss", $room_id, $faculty_id, $subject_code, $subject_name, $day, $start_time, $end_time, $academic_year, $semester);
            if ($ins->execute()) {
                $success = "Schedule added.";
            } else {
                $errors[] = "DB error: " . $conn->error;
            }
            $ins->close();
        } elseif ($mode === 'edit' && $id) {
            $upd = $conn->prepare("UPDATE schedules SET room_id=?, faculty_id=?, subject_code=?, subject_name=?, day_of_week=?, start_time=?, end_time=?, academic_year=?, semester=? WHERE id=?");
            $upd->bind_param("iisssssssi", $room_id, $faculty_id, $subject_code, $subject_name, $day, $start_time, $end_time, $academic_year, $semester, $id);
            if ($upd->execute()) {
                $success = "Schedule updated.";
            } else {
                $errors[] = "DB error: " . $conn->error;
            }
            $upd->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $did = (int) $_GET['delete'];
    $d = $conn->prepare("DELETE FROM schedules WHERE id=?");
    $d->bind_param("i", $did);
    if ($d->execute()) {
        $success = "Schedule deleted.";
    } else {
        $errors[] = "Error deleting: " . $conn->error;
    }
    $d->close();
}

// Fetch schedules for listing (join with rooms & users)
$sql = "SELECT s.*, r.room_code, r.room_name, u.fullname AS faculty_name
        FROM schedules s
        JOIN rooms r ON s.room_id = r.id
        JOIN users u ON s.faculty_id = u.id
        ORDER BY FIELD(day_of_week, 'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), start_time";
$res = $conn->query($sql);
$schedules = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// For edit prefill
$editSchedule = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    foreach ($schedules as $s) {
        if ((int)$s['id'] === $eid) { $editSchedule = $s; break; }
    }
    // If editing, ensure faculties and rooms lists include the relevant ones (they should)
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
                <h1 class="text-3xl text-black pb-6">Schedule Management</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full">
                        <i class="fa-solid fa-calendar"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $totalSched; ?></h2>
                            <p class="text-sm text-gray-500">Total Schedules</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                        <i class="fa-solid fa-book"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $courseSessions; ?></h2>
                            <p class="text-sm text-gray-500">Course Sessions</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-blue-100 text-orange-600 p-3 rounded-full">
                        <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $totalFaculty; ?></h2>
                            <p class="text-sm text-gray-500">Active Faculty</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
                        <i class="fa-solid fa-door-closed"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $occupiedRooms; ?></h2>
                            <p class="text-sm text-gray-500">Occupied Rooms</p>
                        </div>
                    </div>

                </div>

                <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-2 rounded">
                    <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php
                $isEdit = ($editSchedule !== null);
                ?>

                <div class="bg-white shadow rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold mb-3"><?php echo $isEdit ? 'Edit Schedule' : 'Add Schedule'; ?></h2>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="mode" value="<?php echo $isEdit ? 'edit' : 'add'; ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo (int)$editSchedule['id']; ?>"><?php endif; ?>

                    <div>
                    <label class="block text-sm mb-1">Room</label>
                    <select name="room_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Select Room --</option>
                        <?php foreach ($rooms as $r): 
                            $sel = ($isEdit && (int)$r['id'] === (int)$editSchedule['room_id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int)$r['id']; ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($r['room_code'] . ' — ' . $r['room_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Faculty</label>
                    <select name="faculty_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Select Faculty --</option>
                        <?php foreach ($faculties as $f): 
                            $sel = ($isEdit && (int)$f['id'] === (int)$editSchedule['faculty_id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int)$f['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($f['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Day</label>
                    <select name="day_of_week" class="w-full border rounded px-3 py-2" required>
                        <?php
                        $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                        $curr = $isEdit ? $editSchedule['day_of_week'] : '';
                        foreach ($days as $d) {
                            $sel = ($curr === $d) ? 'selected' : '';
                            echo "<option value=\"$d\" $sel>$d</option>";
                        }
                        ?>
                    </select>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Start Time</label>
                    <input type="time" name="start_time" class="w-full border rounded px-3 py-2" required
                        value="<?php echo htmlspecialchars($isEdit ? $editSchedule['start_time'] : ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">End Time</label>
                    <input type="time" name="end_time" class="w-full border rounded px-3 py-2" required
                        value="<?php echo htmlspecialchars($isEdit ? $editSchedule['end_time'] : ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Subject Code</label>
                    <input type="text" name="subject_code" class="w-full border rounded px-3 py-2"
                        value="<?php echo htmlspecialchars($isEdit ? $editSchedule['subject_code'] : ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Subject Name</label>
                    <input type="text" name="subject_name" class="w-full border rounded px-3 py-2"
                        value="<?php echo htmlspecialchars($isEdit ? $editSchedule['subject_name'] : ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Academic Year</label>
                    <input type="text" name="academic_year" class="w-full border rounded px-3 py-2"
                        value="<?php echo htmlspecialchars($isEdit ? $editSchedule['academic_year'] : ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Semester</label>
                    <input type="text" name="semester" class="w-full border rounded px-3 py-2"
                        value="<?php echo htmlspecialchars($isEdit ? $editSchedule['semester'] : ''); ?>">
                    </div>

                    <div class="md:col-span-2 flex justify-end mt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                        <?php echo $isEdit ? 'Update Schedule' : 'Add Schedule'; ?>
                    </button>
                    </div>
                </form>
                </div>

                <!-- Schedules table -->
                <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-3">All Schedules</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                        <th class="px-3 py-2 text-left">ID</th>
                        <th class="px-3 py-2 text-left">Room</th>
                        <th class="px-3 py-2 text-left">Faculty</th>
                        <th class="px-3 py-2 text-left">Day</th>
                        <th class="px-3 py-2 text-left">Time</th>
                        <th class="px-3 py-2 text-left">Subject</th>
                        <th class="px-3 py-2 text-left">AY / Sem</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr><td colspan="8" class="px-3 py-4 text-center text-gray-500">No schedules found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $s): ?>
                            <tr class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2"><?php echo (int)$s['id']; ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($s['room_code'] . ' — ' . $s['room_name']); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($s['faculty_name']); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($s['day_of_week']); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars(substr($s['start_time'],0,5) . ' - ' . substr($s['end_time'],0,5)); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($s['subject_code'] . ' ' . $s['subject_name']); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($s['academic_year'] . ' / ' . $s['semester']); ?></td>
                            <td class="px-3 py-2 text-right">
                                <a href="schedules.php?edit=<?php echo (int)$s['id']; ?>" class="text-blue-600 hover:underline text-xs">Edit</a>
                                <a href="schedules.php?delete=<?php echo (int)$s['id']; ?>" class="text-red-600 hover:underline text-xs" onclick="return confirm('Delete this schedule?');">Delete</a>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    </table>
                </div>
                </div>
            </main>
        </div>
        
    </div>

    <?php include "includes/footer.php"?>
</body>
</html>
