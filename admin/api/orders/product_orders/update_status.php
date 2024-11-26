<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['status_type']) || !isset($data['status_value'])) {
        throw new Exception('缺少必要參數');
    }

    $order_id = intval($data['order_id']);
    $status_type = $data['status_type'];
    $status_value = intval($data['status_value']);

    // 檢查狀態類型
    if (!in_array($status_type, ['payment_status', 'order_status'])) {
        throw new Exception('無效的狀態類型');
    }

    // 更新訂單狀態
    $sql = "UPDATE product_orders SET {$status_type} = ? WHERE order_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$status_value, $order_id]);

    echo json_encode(['success' => true, 'message' => '狀態更新成功']);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}