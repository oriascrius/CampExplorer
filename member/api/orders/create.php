<?php
require_once '../../../camping_db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

try {
    $db->beginTransaction();
    
    // 獲取購物車內容和計算總金額
    $sql = "SELECT c.*, p.price, p.stock, p.name 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cartItems)) {
        throw new Exception('購物車是空的');
    }
    
    // 檢查庫存
    foreach ($cartItems as $item) {
        if ($item['stock'] < $item['quantity']) {
            throw new Exception("商品 {$item['name']} 庫存不足");
        }
    }
    
    // 計算總金額
    $total_amount = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $cartItems));
    
    // 建立訂單
    $sql = "INSERT INTO product_orders (member_id, total_amount) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $total_amount]);
    $order_id = $db->lastInsertId();
    
    // 建立訂單明細
    $sql = "INSERT INTO product_order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    // 更新庫存的SQL
    $update_stock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    
    foreach ($cartItems as $item) {
        // 新增訂單明細
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);
        
        // 更新庫存
        $update_stock->execute([$item['quantity'], $item['product_id']]);
    }
    
    // 清空購物車
    $sql = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    
    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => '訂單建立成功',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log($e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}