<?php
require_once 'includes/auth.php';
requireLogin();

$user = currentUser();
$otherUserId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$otherUser = getUserById($otherUserId);

if (!$otherUser) {
    header('Location: dashboard.php');
    exit();
}

// Mark messages as read when opening chat
markMessagesAsRead($otherUser['id'], $user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($otherUser['username']); ?> - Chat System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar (same as dashboard.php) -->
        <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
            <!-- ... same sidebar content as dashboard.php ... -->
        </div>
        
        <!-- Main content -->
        <div class="flex-1 flex flex-col">
            <!-- Chat header -->
            <div class="p-4 border-b border-gray-200 flex items-center">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <img src="uploads/avatars/<?php echo $otherUser['avatar']; ?>" alt="<?php echo htmlspecialchars($otherUser['username']); ?>" class="w-10 h-10 rounded-full">
                        <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full <?php echo $otherUser['status'] === 'online' ? 'bg-green-500' : 'bg-gray-400'; ?> ring-2 ring-white"></span>
                    </div>
                    <div>
                        <p class="font-medium"><?php echo htmlspecialchars($otherUser['first_name'] . ' ' . $otherUser['last_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo $otherUser['status']; ?></p>
                    </div>
                </div>
                <div class="ml-auto flex space-x-2">
                    <button class="p-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
            </div>
            
            <!-- Messages area -->
            <div id="messages-container" class="flex-1 overflow-y-auto p-4 bg-gray-50 space-y-4">
                <?php 
                $messages = getMessages($user['id'], $otherUser['id']);
                foreach ($messages as $message): 
                    $isMe = $message['sender_id'] == $user['id'];
                ?>
                    <div class="flex <?php echo $isMe ? 'justify-end' : 'justify-start'; ?>">
                        <div class="<?php echo $isMe ? 'bg-indigo-100' : 'bg-white'; ?> rounded-lg p-3 max-w-xs lg:max-w-md shadow">
                            <?php if (!$isMe): ?>
                                <p class="text-xs font-medium text-gray-500 mb-1"><?php echo htmlspecialchars($message['username']); ?></p>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($message['message']); ?></p>
                            <p class="text-right text-xs text-gray-500 mt-1">
                                <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Message input -->
            <div class="p-4 border-t border-gray-200">
                <form id="message-form" class="flex space-x-2">
                    <input type="hidden" id="receiver-id" value="<?php echo $otherUser['id']; ?>">
                    <input type="text" id="message-input" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Scroll to bottom of messages
        const messagesContainer = document.getElementById('messages-container');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Handle message submission
        document.getElementById('message-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('message-input');
            const receiverId = document.getElementById('receiver-id').value;
            const message = messageInput.value.trim();
            
            if (message) {
                // Send message via AJAX
                fetch('api/messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_id: receiverId,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add message to the UI
                        const now = new Date();
                        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        
                        const messageElement = document.createElement('div');
                        messageElement.className = 'flex justify-end';
                        messageElement.innerHTML = `
                            <div class="bg-indigo-100 rounded-lg p-3 max-w-xs lg:max-w-md shadow">
                                <p>${message}</p>
                                <p class="text-right text-xs text-gray-500 mt-1">${timeString}</p>
                            </div>
                        `;
                        
                        messagesContainer.appendChild(messageElement);
                        messageInput.value = '';
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                });
            }
        });
        
        // Poll for new messages
        let lastMessageId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;
        
        function checkForNewMessages() {
            fetch(`api/messages.php?receiver_id=<?php echo $otherUser['id']; ?>&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(messages => {
                    if (messages.length > 0) {
                        messages.forEach(message => {
                            const isMe = message.sender_id == <?php echo $user['id']; ?>;
                            const date = new Date(message.created_at);
                            const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            
                            const messageElement = document.createElement('div');
                            messageElement.className = `flex ${isMe ? 'justify-end' : 'justify-start'}`;
                            messageElement.innerHTML = `
                                <div class="${isMe ? 'bg-indigo-100' : 'bg-white'} rounded-lg p-3 max-w-xs lg:max-w-md shadow">
                                    ${!isMe ? `<p class="text-xs font-medium text-gray-500 mb-1">${message.username}</p>` : ''}
                                    <p>${message.message}</p>
                                    <p class="text-right text-xs text-gray-500 mt-1">${timeString}</p>
                                </div>
                            `;
                            
                            messagesContainer.appendChild(messageElement);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        });
                        
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                });
        }
        
        // Check for new messages every 2 seconds
        setInterval(checkForNewMessages, 2000);
    </script>
</body>
</html>
