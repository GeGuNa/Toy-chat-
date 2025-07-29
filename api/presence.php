<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$user = currentUser();
$data = json_decode(file_get_contents('php://input'), true);


$status = $data['status'] ?? 'online';
updateUserStatus($user['id'], $status);


if (isset($data['expires_at'])) {
    $stmt = $pdo->prepare("UPDATE users SET status_expires_at = ? WHERE id = ?");
    $stmt->execute([$data['expires_at'], $user['id']]);
}

echo json_encode(['success' => true]);
?>
