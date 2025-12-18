<?php
session_start();
require 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, username, password_hash, role, status 
                                FROM users 
                                WHERE username = ? 
                                LIMIT 1");
        if (!$stmt) {
            $error = 'Query error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $fullname, $db_username, $password_hash, $role, $status);
                $stmt->fetch();

                if ($status !== 'active') {
                    $error = 'This account is inactive.';
                } elseif (password_verify($password, $password_hash)) {
                    // Login OK
                    $_SESSION['user_id'] = $id;
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['role'] = $role;

                    header('Location: index.php'); // or dashboard.php
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>TapInTapOut | Admin Login</title>
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900">

  <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

    <!-- LEFT PANEL -->
    <div class="hidden md:flex flex-col justify-center items-center p-10 bg-gradient-to-br from-blue-700 to-blue-900 text-white">
      <div class="mb-6">
      <i class="fa-solid fa-id-card fa-8x"></i>
      </div>

      <h2 class="text-2xl font-bold mb-3">RFID ACCESS CONTROL</h2>
      <p class="text-sm text-blue-200 text-center leading-relaxed max-w-sm">
        Secure authentication system for authorized personnel only.
        Please use your credentials to access the admin dashboard.
      </p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="p-10 flex flex-col justify-center">

      <h2 class="text-3xl font-bold text-blue-800 mb-2">ADMIN LOGIN</h2>
      <p class="text-sm text-gray-500 mb-8">
        Enter your credentials to access the system
      </p>

      <?php if ($error): ?>
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 rounded text-sm">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="space-y-5">

        <!-- USERNAME -->
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Username</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <i class="fa-regular fa-user"></i>
            </span>
            <input
              type="text"
              name="username"
              required
              class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
              placeholder="Enter your username..."
            >
          </div>
        </div>

        <!-- PASSWORD -->
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <i class="fa-solid fa-key"></i>
            </span>
            <input
              type="password"
              name="password"
              required
              class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none"
              placeholder="Enter your password..."
            >
          </div>
        </div>

        <!-- OPTIONS -->
        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center space-x-2 text-gray-600">
            <input type="checkbox" class="rounded border-gray-300">
            <span>Remember me</span>
          </label>
          <a href="#" class="text-blue-600 hover:underline">
            Forgot Password?
          </a>
        </div>

        <!-- BUTTON -->
        <button
          type="submit"
          class="w-full py-3 bg-gradient-to-r from-blue-700 to-blue-900 text-white rounded-lg font-semibold shadow-lg hover:opacity-90 transition"
        >
          ACCESS SYSTEM
        </button>

      </form>

      <p class="mt-6 text-xs text-gray-500 flex items-center justify-center gap-2">
      <i class="fa-solid fa-lock"></i> Secure RFID Authentication System
      </p>
    </div>

  </div>

</body>
</html>
