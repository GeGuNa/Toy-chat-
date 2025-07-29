<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = currentUser();
$chatId = (int)$_GET['chat_id'];
$format = $_GET['format'] ?? 'pdf';


$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages 
    WHERE (sender_id = ? OR receiver_id = ? OR group_id IN (
        SELECT group_id FROM group_members WHERE user_id = ?
    )) AND (id = ? OR group_id = ?)
");
$stmt->execute([$user['id'], $user['id'], $user['id'], $chatId, $chatId]);

if ($stmt->fetchColumn() === 0) {
    http_response_code(403);
    die("Access denied");
}


$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.avatar 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.id = ? OR m.group_id = ?
    ORDER BY m.created_at
");
$stmt->execute([$chatId, $chatId]);
$messages = $stmt->fetchAll();


switch ($format) {
    case 'pdf':
        require_once __DIR__ . '/../libs/fpdf/fpdf.php';
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(40,10,'Chat History Export');
        $pdf->Ln(20);
        
        $pdf->SetFont('Arial','',12);
        foreach ($messages as $msg) {
            $pdf->Cell(0,10,"[{$msg['created_at']}] {$msg['username']}: {$msg['message']}",0,1);
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="chat_export.pdf"');
        echo $pdf->Output('S');
        break;
        
    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="chat_export.json"');
        echo json_encode($messages);
        break;
        
    case 'txt':
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="chat_export.txt"');
        foreach ($messages as $msg) {
            echo "[{$msg['created_at']}] {$msg['username']}: {$msg['message']}\n";
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported export format']);
}
?>
