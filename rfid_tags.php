
<?php
require 'auth.php';
require 'db_connect.php';

// Only admins (or allow faculty if you want)
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

$errors = [];
$success = '';

// Handle Add / Edit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'add';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $rfid_uid = trim($_POST['rfid_uid'] ?? '');
    $user_id = isset($_POST['user_id']) && ctype_digit($_POST['user_id']) ? (int) $_POST['user_id'] : null;
    $label = trim($_POST['label'] ?? '');
    $status = in_array($_POST['status'] ?? 'active', ['active','inactive','lost','blocked']) ? $_POST['status'] : 'active';

    if ($rfid_uid === '') $errors[] = "RFID UID is required.";

    if (empty($errors)) {
        if ($mode === 'add') {
            $stmt = $conn->prepare("INSERT INTO rfid_tags (user_id, rfid_uid, label, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $rfid_uid, $label, $status);
            if ($stmt->execute()) {
                $success = "RFID tag added/assigned successfully.";
            } else {
                $errors[] = "Insert error: " . $conn->error;
            }
            $stmt->close();
        } elseif ($mode === 'edit' && $id) {
            $stmt = $conn->prepare("UPDATE rfid_tags SET user_id=?, rfid_uid=?, label=?, status=? WHERE id=?");
            $stmt->bind_param("isssi", $user_id, $rfid_uid, $label, $status, $id);
            if ($stmt->execute()) {
                $success = "RFID tag updated.";
            } else {
                $errors[] = "Update error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle delete/unassign via GET
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $did = (int) $_GET['delete'];
    $d = $conn->prepare("DELETE FROM rfid_tags WHERE id=?");
    $d->bind_param("i", $did);
    if ($d->execute()) {
        $success = "Tag deleted.";
    } else {
        $errors[] = "Delete error: " . $conn->error;
    }
    $d->close();
}

// Quick search/filter
$filter_user = trim($_GET['filter_user'] ?? '');
$filter_status = $_GET['filter_status'] ?? 'all';
$q = "SELECT t.*, u.fullname, u.username FROM rfid_tags t LEFT JOIN users u ON t.user_id = u.id";
$where = [];
$params = [];
$types = '';

if ($filter_user !== '') {
    $where[] = "(u.fullname LIKE ? OR u.username LIKE ? OR t.label LIKE ? OR t.rfid_uid LIKE ?)";
    $like = "%$filter_user%";
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($filter_status !== 'all' && in_array($filter_status, ['active','inactive','lost','blocked'])) {
    $where[] = "t.status = ?";
    $types .= 's';
    array_push($params, $filter_status);
}
if (!empty($where)) {
    $q .= " WHERE " . implode(" AND ", $where);
}
$q .= " ORDER BY t.created_at DESC, t.id DESC";

// prepared query builder
if ($stmt = $conn->prepare($q)) {
    if (!empty($params)) {
        // bind dynamically
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $tags = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    // fallback: no prepared params
    $res = $conn->query($q);
    $tags = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// fetch users list for assign select
$usersRes = $conn->query("SELECT id, fullname, username FROM users WHERE status='active' ORDER BY fullname");
$users = $usersRes ? $usersRes->fetch_all(MYSQLI_ASSOC) : [];

// edit prefill
$editTag = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    foreach ($tags as $t) {
        if ((int)$t['id'] === $eid) { $editTag = $t; break; }
    }
}
$isEdit = $editTag !== null;

// helper to generate fake UID (for testing)
function gen_fake_uid() {
    $chars = 'ABCDEF0123456789';
    $uid = '';
    for ($i=0;$i<8;$i++) $uid .= $chars[rand(0, strlen($chars)-1)];
    return $uid;
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
                <h1 class="text-3xl text-black pb-6">RFID Tags</h1>

                <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-2 rounded">
                    <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-2 rounded"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Add / Edit form -->
                <div class="bg-white shadow rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold mb-3"><?php echo $isEdit ? 'Edit Tag' : 'Add / Assign Tag'; ?></h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="mode" value="<?php echo $isEdit ? 'edit' : 'add'; ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo (int)$editTag['id']; ?>"><?php endif; ?>

                    <div>
                    <label class="block text-sm mb-1">RFID UID</label>
                    <div class="flex space-x-2">
                        <input type="text" name="rfid_uid" id="rfid_uid" class="w-full border rounded px-3 py-2"
                            value="<?php echo htmlspecialchars($editTag['rfid_uid'] ?? ''); ?>" required>
                    </div>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Assign to User</label>
                    <select name="user_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($users as $u): 
                            $sel = ($isEdit && (int)$u['id'] === (int)$editTag['user_id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int)$u['id']; ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($u['fullname'] . ' (' . $u['username'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Label (optional)</label>
                    <input type="text" name="label" class="w-full border rounded px-3 py-2" 
                            value="<?php echo htmlspecialchars($editTag['label'] ?? ''); ?>">
                    </div>

                    <div>
                    <label class="block text-sm mb-1">Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2">
                        <?php $curr = $editTag['status'] ?? 'active';
                        foreach (['active','inactive','lost','blocked'] as $s) {
                            $sel = ($curr === $s) ? 'selected' : '';
                            echo "<option value=\"$s\" $sel>" . ucfirst($s) . "</option>";
                        } ?>
                    </select>
                    </div>

                    <div class="md:col-span-2 flex justify-end mt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                        <?php echo $isEdit ? 'Update Tag' : 'Add Tag'; ?>
                    </button>
                    </div>
                </form>
                </div>

                <!-- Filters -->
                <div class="mb-4 flex items-center space-x-2">
                <form method="GET" class="flex space-x-2">
                    <input type="text" name="filter_user" placeholder="Search user/uid/label..." value="<?php echo htmlspecialchars($filter_user); ?>"
                        class="border rounded px-3 py-2">
                    <select name="filter_status" class="border rounded px-3 py-2">
                    <option value="all">All statuses</option>
                    <?php foreach (['active','inactive','lost','blocked'] as $s) {
                        $sel = ($filter_status === $s) ? 'selected' : '';
                        echo "<option value=\"$s\" $sel>" . ucfirst($s) . "</option>";
                    } ?>
                    </select>
                    <button class="bg-gray-700 text-white px-3 py-2 rounded">Filter</button>
                </form>
                <a href="rfid_tags.php" class="text-sm text-gray-600">Reset</a>
                </div>

                <!-- Tags table -->
                <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-3">All RFID Tags</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                        <th class="px-3 py-2 text-left">ID</th>
                        <th class="px-3 py-2 text-left">UID</th>
                        <th class="px-3 py-2 text-left">Label</th>
                        <th class="px-3 py-2 text-left">Assigned To</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Created</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tags)): ?>
                        <tr><td colspan="7" class="px-3 py-4 text-center text-gray-500">No tags found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tags as $t): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2"><?php echo (int)$t['id']; ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($t['rfid_uid']); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($t['label']); ?></td>
                            <td class="px-3 py-2"><?php echo htmlspecialchars($t['fullname'] ? $t['fullname'] . ' (' . $t['username'] . ')' : '—'); ?></td>
                            <td class="px-3 py-2">
                            <span class="px-2 py-1 rounded text-xs <?php echo $t['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'; ?>">
                                <?php echo htmlspecialchars($t['status']); ?>
                            </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500"><?php echo htmlspecialchars($t['created_at']); ?></td>
                            <td class="px-3 py-2 text-right space-x-2">
                            <a href="rfid_tags.php?edit=<?php echo (int)$t['id']; ?>" class="text-blue-600 hover:underline text-xs">Edit</a>
                            <a href="rfid_tags.php?delete=<?php echo (int)$t['id']; ?>" class="text-red-600 hover:underline text-xs" onclick="return confirm('Delete this tag?');">Delete</a>
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
