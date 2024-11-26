<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    getMember();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getMember()
{
    global $db;
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception('無效的會員ID');
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        throw new Exception('找不到該會員');
    }

    echo json_encode(['success' => true, 'data' => $member]);
}