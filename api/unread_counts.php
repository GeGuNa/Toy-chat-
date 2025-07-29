<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$user = currentUser();
$response = [
    'total' => 0,
    'conversations' => []
];


$conversations = getRecentConversations($user['id']);
foreach ($conversations as $conv) {
    $response['conversations'][] = [
        'id' => $conv['id'],
        'unread' => (int)$conv['unread_count']
    ];
    $response['total'] += $conv['unread_count'];
}

echo json_encode($response);
?>
