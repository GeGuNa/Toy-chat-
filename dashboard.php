<?php
require_once 'includes/auth.php';
requireLogin();

$user = currentUser();
$conversations = getRecentConversations($user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chat System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h1 class="text-xl font-bold">Chat System</h1>
                <div class="relative">
                    <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                        <img src="uploads/avatars/<?php echo $user['avatar']; ?>" alt="Profile" class="w-8 h-8 rounded-full">
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </button>
                    <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                    </div>
                </div>
            </div>
            
            <!-- Search -->
            <div class="p-4 border-b border-gray-200">
                <div class="relative">
                    <input type="text" placeholder="Search conversations..." class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button class="absolute right-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <!-- Conversations -->
            <div class="flex-1 overflow-y-auto">
                <div class="divide-y divide-gray-200">
                    <?php foreach ($conversations as $conversation): ?>
                        <a href="chat.php?user=<?php echo $conversation['id']; ?>" class="block p-4 hover:bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <div class="relative">
                                    <img src="uploads/avatars/<?php echo $conversation['avatar']; ?>" alt="<?php echo htmlspecialchars($conversation['username']); ?>" class="w-10 h-10 rounded-full">
                                    <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full <?php echo $conversation['status'] === 'online' ? 'bg-green-500' : 'bg-gray-400'; ?> ring-2 ring-white"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($conversation['last_message']); ?></p>
                                </div>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">
                                        <?php echo $conversation['unread_count']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- New conversation button -->
            <div class="p-4 border-t border-gray-200">
                <button class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus mr-2"></i> New Conversation
                </button>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="flex-1 flex flex-col">
            <!-- Chat header (will be filled by JavaScript) -->
            <div id="chat-header" class="p-4 border-b border-gray-200 flex items-center">
                <div class="text-center w-full">
                    <p class="text-gray-500">Select a conversation to start chatting</p>
                </div>
            </div>
            
            <!-- Messages area (will be filled by JavaScript) -->
            <div id="messages-container" class="flex-1 overflow-y-auto p-4 bg-gray-50">
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <i class="fas fa-comments text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">No conversation selected</p>
                    </div>
                </div>
            </div>
            
            <!-- Message input (will be filled by JavaScript) -->
            <div id="message-input-container" class="p-4 border-t border-gray-200 hidden">
                <form id="message-form" class="flex space-x-2">
                    <input type="text" id="message-input" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            document.getElementById('user-menu').classList.toggle('hidden');
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const userMenuButton = document.getElementById('user-menu-button');
            
            if (!userMenu.contains(event.target) && !userMenuButton.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
