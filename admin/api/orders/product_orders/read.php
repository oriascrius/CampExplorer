<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    // 檢查資料庫連接
    if (!isset($db) || !$db) {
        throw new Exception("資料庫連接失敗");
    }

    // 基礎 SQL 查詢，先測試資料表是否存在
    $checkTable = $db->query("SHOW TABLES LIKE 'product_orders'");
    if ($checkTable->rowCount() === 0) {
        throw new Exception("找不到訂單資料表");
    }

    // 獲取排序參數
    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';
    
    // 允許的排序欄位和順序
    $allowed_fields = ['order_id', 'name', 'total_amount', 'payment_status', 'order_status', 'created_at'];
    $allowed_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_field, $allowed_fields)) {
        $sort_field = 'created_at';
    }
    if (!in_array($sort_order, $allowed_orders)) {
        $sort_order = 'DESC';
    }

    // 修正 SQL 查詢
    $sql = "SELECT po.*, 
            u.name as username,
            (SELECT COUNT(*) FROM product_order_details WHERE order_id = po.order_id) as items_count
            FROM product_orders po
            LEFT JOIN users u ON po.member_id = u.id 
            ORDER BY " . 
            ($sort_field === 'name' ? 'u.name' : 'po.' . $sort_field) . 
            " " . $sort_order;
            
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'data' => $orders
        ]);
        
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage() . "\nSQL: " . $sql);
        throw new Exception("資料庫查詢失敗");
    }

} catch (Exception $e) {
    error_log("Error in read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '資料載入失敗，請稍後再試',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
}