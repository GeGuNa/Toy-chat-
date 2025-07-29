<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$user = currentUser();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
   
    $name = substr(sanitize($data['name'] ?? ''), 0, 100);
    $members = array_map('intval', $data['members'] ?? []);
    $members = array_unique(array_filter($members));
    
    if (empty($name) || count($members) < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Group name and at least one member required']);
        exit();
    }
    
   
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (?, ?)");
        $stmt->execute([$name, $user['id']]);
        $groupId = $pdo->lastInsertId();
        
      
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, TRUE)");
        $stmt->execute([$groupId, $user['id']]);
        
    
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        foreach ($members as $memberId) {
            if ($memberId !== $user['id']) {
                $stmt->execute([$groupId, $memberId]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'group_id' => $groupId,
            'name' => $name
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create group: ' . $e->getMessage()]);
    }
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT g.*, 
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM groups g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $groups = $stmt->fetchAll();
    
    echo json_encode($groups);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $groupId = (int)$_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
 
    $stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? AND is_admin = TRUE");
    $stmt->execute([$groupId, $user['id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Only group admins can modify groups']);
        exit();
    }
    
 
    if (!empty($data['name'])) {
        $name = substr(sanitize($data['name']), 0, 100);
        $stmt = $pdo->prepare("UPDATE groups SET name = ? WHERE id = ?");
        $stmt->execute([$name, $groupId]);
    }
    
   
    if (isset($data['members'])) {
        $newMembers = array_map('intval', $data['members']);
        $newMembers = array_unique(array_filter($newMembers));
        
       
        $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $currentMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
       
        $toAdd = array_diff($newMembers, $currentMembers);
        if (!empty($toAdd)) {
            $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            foreach ($toAdd as $memberId) {
                $stmt->execute([$groupId, $memberId]);
            }
        }
        
   
        $toRemove = array_diff($currentMembers, $newMembers);
        if (!empty($toRemove)) {
            $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND is_admin = FALSE");
            foreach ($toRemove as $memberId) {
                $stmt->execute([$groupId, $memberId]);
            }
        }
    }
    
    echo json_encode(['success' => true]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $groupId = (int)$_GET['id'];
    
  
    $stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? AND is_admin = TRUE");
    $stmt->execute([$groupId, $user['id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Only group admins can delete groups']);
        exit();
    }
    

    $stmt = $pdo->prepare("DELETE FROM groups WHERE id = ?");
    $stmt->execute([$groupId]);
    
    echo json_encode(['success' => true]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
