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
$product_id = $data['product_id'] ?? 0;
$quantity = $data['quantity'] ?? 1;

try {
    // 檢查商品是否存在且有庫存
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '商品不存在']);
        exit;
    }
    
    // 檢查購物車是否已有此商品
    $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    $cartItem = $stmt->fetch();
    
    if ($cartItem) {
        // 更新數量
        $stmt = $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$quantity, $cartItem['id']]);
    } else {
        // 新增到購物車
        $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
    }
    
    echo json_encode(['success' => true, 'message' => '已加入購物車']);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '加入購物車失敗，請稍後再試']);
}