<?php
require_once '../includes/auth.php';
require_once '../config/openai.php'; 

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$prompt = sanitize($data['prompt']);


$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.7
]));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
echo json_encode([
    'response' => $result['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.'
]);
?>
