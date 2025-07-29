<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$user = currentUser();
$data = json_decode(file_get_contents('php://input'), true);


$status = substr(sanitize($data['status'] ?? ''), 0, 100);
$emoji = substr(sanitize($data['emoji'] ?? ''), 0, 5);
$expiresAt = !empty($data['expires_at']) ? date('Y-m-d H:i:s', strtotime($data['expires_at'])) : null;


$stmt = $pdo->prepare("
    UPDATE users 
    SET custom_status = ?, status_emoji = ?, status_expires_at = ?
    WHERE id = ?
");
$stmt->execute([$status, $emoji, $expiresAt, $user['id']]);


if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->publish('user-status', json_encode([
            'user_id' => $user['id'],
            'status' => $status,
            'emoji' => $emoji,
            'expires_at' => $expiresAt
        ]));
    } catch (Exception $e) {
        error_log("Redis error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'status' => $status,
    'emoji' => $emoji,
    'expires_at' => $expiresAt
]);
?>
