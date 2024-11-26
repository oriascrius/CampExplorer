<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) throw new Exception('缺少文章ID');

    $sql = "UPDATE articles SET status = NOT status, updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => '狀態更新成功']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}