<?php
require_once 'auth.php';
$user = currentUser();
?>

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <div>
                <a href="dashboard.php" class="text-xl font-bold text-indigo-600">Chat System</a>
            </div>
            <nav class="hidden md:flex space-x-8">
                <a href="dashboard.php" class="text-gray-900 hover:text-indigo-600">Chat</a>
                <a href="profile.php" class="text-gray-900 hover:text-indigo-600">Profile</a>
                <a href="settings.php" class="text-gray-900 hover:text-indigo-600">Settings</a>
                <?php if ($user['is_admin']): ?>
                    <a href="admin/dashboard.php" class="text-gray-900 hover:text-indigo-600">Admin</a>
                <?php endif; ?>
            </nav>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button id="header-user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                        <img src="uploads/avatars/<?php echo $user['avatar']; ?>" alt="Profile" class="w-8 h-8 rounded-full">
                        <span class="hidden md:inline"><?php echo htmlspecialchars($user['username']); ?></span>
                    </button>
                    <div id="header-user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Toggle user menu
    document.getElementById('header-user-menu-button').addEventListener('click', function() {
        document.getElementById('header-user-menu').classList.toggle('hidden');
    });

    // Close user menu when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('header-user-menu');
        const userMenuButton = document.getElementById('header-user-menu-button');
        
        if (!userMenu.contains(event.target) && !userMenuButton.contains(event.target)) {
            userMenu.classList.add('hidden');
        }
    });
</script>
