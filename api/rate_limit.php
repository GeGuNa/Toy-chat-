<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

$user = currentUser();
$ip = $_SERVER['REMOTE_ADDR'];
$endpoint = $_SERVER['REQUEST_URI'];


$stmt = $pdo->prepare("INSERT INTO rate_limits (user_id, ip, endpoint) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $ip, $endpoint]);


$stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits 
                      WHERE ip = ? AND created_at > NOW() - INTERVAL 1 MINUTE");
$stmt->execute([$ip]);
$count = $stmt->fetchColumn();

if ($count > 60) { 
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit();
}


?>
