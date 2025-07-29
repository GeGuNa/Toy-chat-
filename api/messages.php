<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user = currentUser();



if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $receiverId = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    if ($receiverId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid receiver ID']);
        exit();
    }
    

    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user['id'], $receiverId, $receiverId, $user['id'], $lastId]);
    $messages = $stmt->fetchAll();
    
    echo json_encode($messages);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $receiverId = isset($data['receiver_id']) ? (int)$data['receiver_id'] : 0;
    $message = isset($data['message']) ? sanitize($data['message']) : '';
    
    if ($receiverId <= 0 || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit();
    }
    
    if (sendMessage($user['id'], $receiverId, $message)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
    exit();
}




if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);
    $messageId = (int)$_GET['id'];
    

    $stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message || ($message['sender_id'] !== $user['id'] && !$user['is_admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit();
    }
    

    $updates = [];
    $params = [];
    
    if (isset($data['is_pinned'])) {
        $updates[] = 'is_pinned = ?';
        $params[] = (bool)$data['is_pinned'];
    }
    
    if (isset($data['is_starred'])) {
        $updates[] = 'is_starred = ?';
        $params[] = (bool)$data['is_starred'];
    }
    
    if (!empty($updates)) {
        $params[] = $messageId;
        $stmt = $pdo->prepare("UPDATE messages SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No valid updates provided']);
    }
    exit();
}









http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
