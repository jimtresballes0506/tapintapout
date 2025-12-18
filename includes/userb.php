<!-- Desktop Header -->
<header class="w-full items-center bg-white py-2 px-6 hidden sm:flex">
    <div class="w-1/2"></div>

    <?php
    // Fetch profile photo
    $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userPhoto = $stmt->get_result()->fetch_assoc()['profile_photo'] ?? null;
    $stmt->close();

    $avatar = $userPhoto ?: 'assets/default-avatar.png';
    ?>

    <div x-data="{ isOpen: false }" class="relative w-1/2 flex justify-end">
        <!-- Avatar -->
        <div
            class="relative z-10 w-12 h-12 rounded-full overflow-hidden border-2 border-gray-300 hover:border-blue-500 focus:outline-none">
            <img
                src="<?php echo htmlspecialchars($avatar); ?>"
                class="w-full h-full object-cover"
                alt="User Avatar"
            >
        </div>
    </div>
</header>
