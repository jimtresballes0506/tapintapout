<?php
require 'auth.php';
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

$stmt = $conn->prepare("SELECT username, profile_photo FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (isset($_POST['update_username'])) {
    $new_username = trim($_POST['username']);

    if ($new_username === '') {
        $errors[] = "Username cannot be empty.";
    } else {
        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username=? AND id!=?");
        $check->bind_param("si", $new_username, $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "Username already taken.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
            $stmt->bind_param("si", $new_username, $user_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['username'] = $new_username;
            $success = "Username updated successfully.";
            $user['username'] = $new_username;
        }
        $check->close();
    }
}

/* =========================
   UPDATE PASSWORD
========================= */
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $stmt->bind_param("si", $hash, $user_id);
        $stmt->execute();
        $stmt->close();

        $success = "Password updated successfully.";
    }
}

if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {

    $file = $_FILES['photo'];

    if ($file['error'] === 0) {

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, PNG, or WEBP images allowed.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image must be under 2MB.";
        } else {
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $path = 'uploads/profile/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $path)) {
                $stmt = $conn->prepare("UPDATE users SET profile_photo=? WHERE id=?");
                $stmt->bind_param("si", $path, $user_id);
                $stmt->execute();
                $stmt->close();

                $success = "Profile photo updated.";
                $user['profile_photo'] = $path;
            } else {
                $errors[] = "Upload failed.";
            }
        }
    }
}

?>

<?php include "includes/header.php"; ?>

<body class="bg-gray-100 font-family-karla flex">

<?php include "includes/sidebar.php"; ?>

<div class="relative w-full flex flex-col h-screen overflow-y-hidden">

<?php include "includes/userb.php"; ?>
<?php include "includes/navbar.php"; ?>

<div class="w-full h-screen overflow-x-hidden border-t flex flex-col">
<main class="w-full flex-grow p-6">

    <!-- PAGE TITLE -->
    <h1 class="text-3xl text-black pb-6">Settings</h1>
    
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


    <!-- PROFILE PICTURE -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Profile Picture</h2>

        <div class="flex items-center space-x-6">
        <img
            src="<?php echo htmlspecialchars($user['profile_photo'] ?: 'assets/default-avatar.png'); ?>"
            class="w-24 h-24 rounded-full object-cover border"
        >

            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="photo" class="mb-2 text-sm">
                <button
                    name="upload_photo"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm"
                >
                    Upload New Photo
                </button>
            </form>
        </div>
    </div>

    <!-- CHANGE USERNAME -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Change Username</h2>

        <form method="POST" class="max-w-md">
            <label class="block text-sm mb-1">Username</label>
            <input
                type="text"
                name="username"
                value="<?php echo htmlspecialchars($user['username']); ?>"
                class="w-full border rounded px-3 py-2 mb-3"
                required
            >

            <button
                name="update_username"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm"
            >
                Update Username
            </button>
        </form>
    </div>

    <!-- CHANGE PASSWORD -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Change Password</h2>

        <form method="POST" class="max-w-md grid grid-cols-1 gap-3">
            <div>
                <label class="block text-sm mb-1">Current Password</label>
                <input type="password" name="current_password"
                       class="w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm mb-1">New Password</label>
                <input type="password" name="new_password"
                       class="w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password"
                       class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mt-2">
                <button
                    name="update_password"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm"
                >
                    Update Password
                </button>
            </div>
        </form>
    </div>

</main>
</div>
</div>

<?php include "includes/footer.php"; ?>
</body>
</html>
