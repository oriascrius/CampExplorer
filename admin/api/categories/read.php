<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    if (isset($_GET['category_id'])) {
        // 如果有 category_id 參數，返回對應的子分類
        $stmt = $db->prepare("SELECT id, name FROM subcategories 
                             WHERE category_id = ? AND status = 1 
                             ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$_GET['category_id']]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 否則返回所有主分類
        $stmt = $db->query("SELECT id, name FROM categories 
                           WHERE status = 1 
                           ORDER BY sort_order ASC, name ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'message' => '讀取成功',
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}