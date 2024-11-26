<?php
require_once '../../../camping_db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'] ?? 0;

try {
    // 檢查購物車項目是否存在且屬於當前用戶
    $stmt = $db->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $cartItem = $stmt->fetch();
    
    if (!$cartItem) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '購物車項目不存在']);
        exit;
    }
    
    // 刪除項目
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->execute([$cart_id]);
    
    echo json_encode(['success' => true, 'message' => '商品已移除']);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '移除失敗，請稍後再試']);
}