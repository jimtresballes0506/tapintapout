<?php
require 'auth.php';
require 'db_connect.php';

$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

$activeUsers = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE status = 'active'
")->fetch_assoc()['total'];

$totalFaculty = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'faculty'
")->fetch_assoc()['total'];

$totalAdmin = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'admin'
")->fetch_assoc()['total'];

// Optional: only allow admins
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

$errors = [];
$success = '';

// Handle form submissions (add / edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode       = $_POST['mode'] ?? 'add';
    $id         = $_POST['id'] ?? null;
    $fullname   = trim($_POST['fullname'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $role       = $_POST['role'] ?? 'student';
    $status     = $_POST['status'] ?? 'active';
    $password   = $_POST['password'] ?? '';

    if ($fullname === '' || $username === '') {
        $errors[] = "Fullname and username are required.";
    }

    if ($mode === 'add' && $password === '') {
        $errors[] = "Password is required for new users.";
    }

    if (empty($errors)) {
        if ($mode === 'add') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users 
                (fullname, username, email, student_id, employee_id, role, password_hash, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss",
                $fullname, $username, $email, $student_id, $employee_id, $role, $hash, $status
            );
            if ($stmt->execute()) {
                $success = "User added successfully.";
            } else {
                $errors[] = "Error inserting user: " . $conn->error;
            }
            $stmt->close();
        } elseif ($mode === 'edit' && $id) {
            if ($password !== '') {
                // Update including password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users
                    SET fullname=?, username=?, email=?, student_id=?, employee_id=?,
                        role=?, status=?, password_hash=?
                    WHERE id=?");
                $stmt->bind_param("ssssssssi",
                    $fullname, $username, $email, $student_id, $employee_id,
                    $role, $status, $hash, $id
                );
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users
                    SET fullname=?, username=?, email=?, student_id=?, employee_id=?,
                        role=?, status=?
                    WHERE id=?");
                $stmt->bind_param("sssssssi",
                    $fullname, $username, $email, $student_id, $employee_id,
                    $role, $status, $id
                );
            }

            if ($stmt->execute()) {
                $success = "User updated successfully.";
            } else {
                $errors[] = "Error updating user: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle deactivate / activate via GET
if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($currStatus);
    if ($stmt->fetch()) {
        $newStatus = ($currStatus === 'active') ? 'inactive' : 'active';
        $stmt->close();

        $u = $conn->prepare("UPDATE users SET status=? WHERE id=?");
        $u->bind_param("si", $newStatus, $id);
        $u->execute();
        $u->close();
        $success = "User status changed to $newStatus.";
    } else {
        $stmt->close();
    }
}

// Fetch all users for listing
$result = $conn->query("SELECT * FROM users ORDER BY role, fullname");
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
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
                <h1 class="text-3xl text-black pb-6">User Management</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full">
                        <i class="fas fa-user"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $totalUsers; ?></h2>
                            <p class="text-sm text-gray-500">Total Users</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                        <i class="fas fa-door-closed"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $activeUsers; ?></h2>
                            <p class="text-sm text-gray-500">Active Users</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-blue-100 text-orange-600 p-3 rounded-full">
                        <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $totalFaculty; ?></h2>
                            <p class="text-sm text-gray-500">Faculty Members</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
                        <i class="fa-brands fa-black-tie"></i>
                        </div>
                        <div>
                        <h2 class="text-2xl font-bold"><?php echo $totalAdmin; ?></h2>
                            <p class="text-sm text-gray-500">Admin Users</p>
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

                <!-- Add / Edit form -->
                <?php
                // If edit mode, prefill form with selected user
                $editUser = null;
                if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
                    $editId = (int) $_GET['edit'];
                    foreach ($users as $u) {
                        if ((int)$u['id'] === $editId) {
                            $editUser = $u;
                            break;
                        }
                    }
                }
                $isEdit = $editUser !== null;
                ?>

                <div class="bg-white shadow rounded-lg p-4 mb-6">
                    <h2 class="text-lg font-semibold mb-3"><?php echo $isEdit ? 'Edit User' : 'Add New User'; ?></h2>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="hidden" name="mode" value="<?php echo $isEdit ? 'edit' : 'add'; ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$editUser['id']; ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm mb-1">Fullname</label>
                            <input type="text" name="fullname" class="w-full border rounded px-3 py-2"
                                value="<?php echo htmlspecialchars($editUser['fullname'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Username</label>
                            <input type="text" name="username" class="w-full border rounded px-3 py-2"
                                value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Email</label>
                            <input type="email" name="email" class="w-full border rounded px-3 py-2"
                                value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Employee ID</label>
                            <input type="text" name="employee_id" class="w-full border rounded px-3 py-2"
                                value="<?php echo htmlspecialchars($editUser['employee_id'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Role</label>
                            <select name="role" class="w-full border rounded px-3 py-2">
                                <?php
                                $currentRole = $editUser['role'] ?? 'student';
                                foreach (['admin','faculty','student'] as $r) {
                                    $sel = ($currentRole === $r) ? 'selected' : '';
                                    echo "<option value=\"$r\" $sel>" . ucfirst($r) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Status</label>
                            <select name="status" class="w-full border rounded px-3 py-2">
                                <?php
                                $currentStatus = $editUser['status'] ?? 'active';
                                foreach (['active','inactive'] as $s) {
                                    $sel = ($currentStatus === $s) ? 'selected' : '';
                                    echo "<option value=\"$s\" $sel>" . ucfirst($s) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">
                                <?php echo $isEdit ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                            </label>
                            <input type="password" name="password" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="md:col-span-2 flex justify-end mt-2">
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                                <?php echo $isEdit ? 'Update User' : 'Add User'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Users table -->
                <div class="bg-white shadow rounded-lg p-4">
                    <h2 class="text-lg font-semibold mb-3">All Users</h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-3 py-2 text-left">ID</th>
                                    <th class="px-3 py-2 text-left">Fullname</th>
                                    <th class="px-3 py-2 text-left">Username</th>
                                    <th class="px-3 py-2 text-left">Role</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-left">Created</th>
                                    <th class="px-3 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="px-3 py-4 text-center text-gray-500">
                                        No users found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-3 py-2"><?php echo (int)$u['id']; ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($u['fullname']); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td class="px-3 py-2 capitalize"><?php echo htmlspecialchars($u['role']); ?></td>
                                        <td class="px-3 py-2 capitalize">
                                            <span class="px-2 py-1 rounded text-xs
                                                <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'; ?>">
                                                <?php echo htmlspecialchars($u['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-500"><?php echo htmlspecialchars($u['created_at']); ?></td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            <a href="users.php?edit=<?php echo (int)$u['id']; ?>"
                                            class="text-blue-600 hover:underline text-xs">Edit</a>
                                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                                <a href="users.php?toggle=<?php echo (int)$u['id']; ?>"
                                                class="text-red-600 hover:underline text-xs"
                                                onclick="return confirm('Change status for this user?');">
                                                    <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </a>
                                            <?php endif; ?>
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
