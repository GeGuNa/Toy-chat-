<?php
require_once __DIR__ . '/../config/database.php';

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Upload avatar
function uploadAvatar($file) {
    $targetDir = __DIR__ . "/../uploads/avatars/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '-' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2000000) {
        return ['success' => false, 'message' => 'File is too large.'];
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

// Get user by ID
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get all users except current user
function getAllUsers($excludeUserId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, avatar, status FROM users WHERE id != ? ORDER BY status DESC, username ASC");
    $stmt->execute([$excludeUserId]);
    return $stmt->fetchAll();
}

// Get messages between two users
function getMessages($userId1, $userId2, $limit = 20, $offset = 0) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.avatar 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId1, $userId2, $userId2, $userId1, $limit, $offset]);
    return array_reverse($stmt->fetchAll());
}

// Send message
function sendMessage($senderId, $receiverId, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    return $stmt->execute([$senderId, $receiverId, $message]);
}

// Mark messages as read
function markMessagesAsRead($senderId, $receiverId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE");
    $stmt->execute([$senderId, $receiverId]);
}

// Get unread message count
function getUnreadMessageCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}

// Get recent conversations
function getRecentConversations($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, u.avatar, u.status, 
               MAX(m.created_at) as last_message_time,
               (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = FALSE) as unread_count
        FROM users u
        JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
        WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
        GROUP BY u.id
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    return $stmt->fetchAll();
}
?>
