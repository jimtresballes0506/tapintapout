<aside class="relative bg-sidebar h-screen w-64 hidden sm:block shadow-xl">
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

    <div class="p-6 border-b border-blue-700">
        <a href="index.php" class="text-white text-2xl font-bold tracking-wide">
            TAPINTAPOUT
        </a>
        <p class="text-xs text-blue-200 mt-1">
            RFID Access Control
        </p>
    </div>

    <nav class="text-white text-base font-semibold pt-3">
        
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

        <div class="my-4 mx-6 border-t border-blue-700 opacity-50"></div>

        <!-- SETTINGS -->
        <a href="settings.php"
           class="flex items-center py-4 pl-6 nav-item
           <?php echo ($current_page == 'settings.php') ? 'active-nav-link' : 'opacity-75 hover:opacity-100'; ?>">
            <i class="fas fa-cog mr-3"></i> Settings
        </a>

        <div class="flex items-center justify-between px-6 py-4 border-t border-blue-700">
            <span class="text-white flex items-center gap-2">
                <i class="fas fa-moon"></i> Dark Mode
            </span>

            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="darkToggle" class="hidden">

                <!-- Track -->
                <div id="toggleTrack"
                    class="w-11 h-6 bg-blue-300 rounded-full transition-colors duration-300">
                </div>

                <!-- Knob -->
                <div id="toggleKnob"
                    class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full
                            transform transition-transform duration-300">
                </div>

            </label>
        </div>

    </div>



        <!-- LOGOUT -->
        <a href="logout.php"
           class="flex items-center py-4 pl-6 nav-item text-red-200 hover:text-white hover:bg-red-600 transition">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>

    </nav>
    
</aside>
