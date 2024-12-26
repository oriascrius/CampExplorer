<?php
require_once __DIR__ . '/../../../camping_db.php';

// 添加 CORS 和快取控制標頭
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

try {
    // 獲取最新統計數據
    $stats = [
        'users' => [
            'total' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'today' => $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn()
        ],
        'camps' => [
            'pending' => $db->query("SELECT COUNT(*) FROM camp_applications WHERE status = 0")->fetchColumn(),
            'total' => $db->query("SELECT COUNT(*) FROM camp_applications")->fetchColumn()
        ],
        'products' => [
            'low_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock <= 10")->fetchColumn(),
            'out_of_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn()
        ],
        'discussions' => [
            'pending' => $db->query("SELECT COUNT(*) FROM user_discussions WHERE status = 'pending'")->fetchColumn()
        ],
        'revenue' => [
            'today' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM product_orders WHERE DATE(created_at) = CURDATE() AND payment_status = 1")->fetchColumn()
        ]
    ];

    // 添加通知相關數據
    $notifications = [
        // 營地申請通知
        'new_camps' => $db->query("
            SELECT COUNT(*) 
            FROM camp_applications 
            WHERE status = 0 
            AND DATE(created_at) = CURDATE()"
        )->fetchColumn(),
        
        // 低庫存商品通知
        'low_stock' => $db->query("
            SELECT COUNT(*) 
            FROM products 
            WHERE stock <= 10 
            AND DATE(updated_at) = CURDATE()"
        )->fetchColumn(),
        
        // 新討論通知
        'new_discussions' => $db->query("
            SELECT COUNT(*) 
            FROM user_discussions 
            WHERE status = 'pending' 
            AND DATE(created_at) = CURDATE()"
        )->fetchColumn(),
        
        // 新訂單通知
        'new_orders' => $db->query("
            SELECT COUNT(*) 
            FROM product_orders 
            WHERE DATE(created_at) = CURDATE() 
            AND order_status = 'pending'"
        )->fetchColumn(),
        
        // 新用戶註冊通知
        'new_users' => $db->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn()
    ];
    
    $stats['notifications'] = $notifications;

    // 添加時間戳到回應
    $stats['timestamp'] = date('Y-m-d H:i:s');

    echo json_encode($stats);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '獲取數據失敗',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    error_log("Dashboard Stats API Error: " . $e->getMessage());
}