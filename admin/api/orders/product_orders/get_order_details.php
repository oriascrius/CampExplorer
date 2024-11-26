<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['order_id'])) {
        throw new Exception('訂單編號未提供');
    }

    $orderId = intval($_GET['order_id']);
    
    // 查詢訂單主要資料
    $sql = "SELECT po.*, u.name as username, u.email, u.phone
            FROM product_orders po 
            LEFT JOIN users u ON po.member_id = u.id 
            WHERE po.order_id = ?";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('找不到訂單資料');
    }

    // 查詢訂單明細，修正圖片路徑處理
    $sql = "SELECT pod.*, 
            p.name as product_name, 
            CASE 
                WHEN pi.image_path IS NOT NULL AND pi.is_main = 1 
                    THEN pi.image_path
                WHEN p.main_image IS NOT NULL 
                    THEN p.main_image
                ELSE 'no-image.jpg'
            END as product_image
            FROM product_order_details pod
            LEFT JOIN products p ON pod.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE pod.order_id = ?";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'data' => $order
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>