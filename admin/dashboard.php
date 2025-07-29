<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
if (!$user['is_admin']) {
    header('Location: /dashboard.php');
    exit();
}

// Get stats for dashboard
global $pdo;
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$messagesCount = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$blogsCount = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'online'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Chat System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Admin Dashboard</h1>
        </div>
        
        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold"><?php echo $usersCount; ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Messages</p>
                        <p class="text-2xl font-bold"><?php echo $messagesCount; ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Notes</p>
                        <p class="text-2xl font-bold"><?php echo $blogsCount; ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Active Users</p>
                        <p class="text-2xl font-bold"><?php echo $activeUsers; ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent activity -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-medium mb-4">Recent Activity</h2>
                <div class="space-y-4">
                    <?php
                    // Get recent messages
                    $recentMessages = $pdo->query("
                        SELECT m.*, u1.username as sender_username, u2.username as receiver_username
                        FROM messages m
                        JOIN users u1 ON m.sender_id = u1.id
                        JOIN users u2 ON m.receiver_id = u2.id
                        ORDER BY m.created_at DESC
                        LIMIT 5
                    ")->fetchAll();
                    
                    foreach ($recentMessages as $message):
                    ?>
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <img src="../uploads/avatars/default.png" alt="User" class="h-8 w-8 rounded-full">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($message['sender_username']); ?> to <?php echo htmlspecialchars($message['receiver_username']); ?>
                                </p>
                                <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($message['message']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-medium mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="users.php" class="block px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                        Manage Users
                    </a>
                    <a href="#" class="block px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                        View All Messages
                    </a>
                    <a href="#" class="block px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                        System Settings
                    </a>
                    <a href="#" class="block px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                        Backup Database
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
