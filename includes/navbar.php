<!-- Mobile Header & Nav -->
<header x-data="{ isOpen: false }" class="w-full bg-sidebar py-5 px-6 sm:hidden">
    <div class="flex items-center justify-between">
        <a href="index.php" class="text-white text-3xl font-semibold uppercase hover:text-gray-300">TapInTapOut</a>
        <button @click="isOpen = !isOpen" class="text-white text-3xl focus:outline-none">
            <i x-show="!isOpen" class="fas fa-bars"></i>
            <i x-show="isOpen" class="fas fa-times"></i>
        </button>
    </div>

    <!-- Dropdown Nav -->
    <nav :class="isOpen ? 'flex': 'hidden'" class="flex flex-col pt-4">
        <!-- Dashboard -->
        <a href="index.php"
           class="flex items-center py-4 pl-6 nav-item 
           <?php echo ($current_page == 'index.php') ? 'active-nav-link text-white' : 'text-white opacity-75 hover:opacity-100'; ?>">
            <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
        </a>

        <!-- User Management (Admins Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="users.php"
               class="flex items-center py-4 pl-6 nav-item
               <?php echo ($current_page == 'users.php') ? 'active-nav-link text-white' : 'text-white opacity-75 hover:opacity-100'; ?>">
                <i class="fas fa-users mr-3"></i> User Management
            </a>
        <?php endif; ?>

        <!-- User Management (Admins Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="rooms.php"
               class="flex items-center py-4 pl-6 nav-item
               <?php echo ($current_page == 'rooms.php') ? 'active-nav-link text-white' : 'text-white opacity-75 hover:opacity-100'; ?>">
                <i class="fas fa-door-closed mr-3"></i> Room Management
            </a>
        <?php endif; ?>

        <!-- User Management (Admins Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="schedules.php"
               class="flex items-center py-4 pl-6 nav-item
               <?php echo ($current_page == 'schedules.php') ? 'active-nav-link text-white' : 'text-white opacity-75 hover:opacity-100'; ?>">
                <i class="fas fa-list mr-3"></i> Schedule Management
            </a>
        <?php endif; ?>

        <!-- User Management (Admins Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="logs.php"
               class="flex items-center py-4 pl-6 nav-item
               <?php echo ($current_page == 'logs.php') ? 'active-nav-link text-white' : 'text-white opacity-75 hover:opacity-100'; ?>">
                <i class="fas fa-clock mr-3"></i> Access Logs
            </a>
        <?php endif; ?>

        <!-- User Management (Admins Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="rfid_tags.php"
               class="flex items-center py-4 pl-6 nav-item
               <?php echo ($current_page == 'rfid_tags.php') ? 'active-nav-link text-white' : 'text-white opacity-75 hover:opacity-100'; ?>">
                <i class="fas fa-tag mr-3"></i> RFID Tags
            </a>
        <?php endif; ?>
    </nav>
</header>