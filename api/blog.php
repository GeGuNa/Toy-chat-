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

// Get notes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 6;
    $offset = ($page - 1) * $limit;
    
    // Only show public notes unless it's the user's own notes or admin
    $showPrivate = ($userId === $user['id'] || $user['is_admin']);
    
    global $pdo;
    $where = $showPrivate ? "user_id = ?" : "user_id = ? AND is_public = TRUE";
    $params = [$userId];
    
    $stmt = $pdo->prepare("
        SELECT * FROM blogs 
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt->execute($params);
    $notes = $stmt->fetchAll();
    
    echo json_encode($notes);
    exit();
}

// Create note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $title = isset($data['title']) ? sanitize($data['title']) : '';
    $content = isset($data['content']) ? sanitize($data['content']) : '';
    $isPublic = isset($data['is_public']) ? (bool)$data['is_public'] : true;
    $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    
    if (empty($title) || empty($content) || $userId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit();
    }
    
    // Verify user can create note for this user
    if ($userId !== $user['id'] && !$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit();
    }
    
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO blogs (user_id, title, content, is_public) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$userId, $title, $content, $isPublic])) {
        $noteId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
        $stmt->execute([$noteId]);
        echo json_encode($stmt->fetch());
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create note']);
    }
    exit();
}

// Delete note
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $noteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($noteId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid note ID']);
        exit();
    }
    
    // Verify user can delete this note
    global $pdo;
    $stmt = $pdo->prepare("SELECT user_id FROM blogs WHERE id = ?");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch();
    
    if (!$note) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
        exit();
    }
    
    if ($note['user_id'] !== $user['id'] && !$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    if ($stmt->execute([$noteId])) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete note']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
