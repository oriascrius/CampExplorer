<?php
require_once __DIR__ . '/../../../camping_db.php';

// 添加錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        'new_camps' => $db->query("
            SELECT COUNT(*) 
            FROM camp_applications 
            WHERE status = 0 
            AND DATE(created_at) = CURDATE()"
        )->fetchColumn(),
        
        'low_stock' => $db->query("
            SELECT COUNT(*) 
            FROM products 
            WHERE stock <= 10 
            AND DATE(updated_at) = CURDATE()"
        )->fetchColumn(),
        
        'new_discussions' => $db->query("
            SELECT COUNT(*) 
            FROM user_discussions 
            WHERE status = 'pending' 
            AND DATE(created_at) = CURDATE()"
        )->fetchColumn(),
        
        'new_orders' => $db->query("
            SELECT COUNT(*) 
            FROM product_orders 
            WHERE DATE(created_at) = CURDATE() 
            AND order_status = 'pending'"
        )->fetchColumn(),
        
        'new_users' => $db->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn()
    ];
    
    $stats['notifications'] = $notifications;

    // 獲取近期活動紀錄 - 使用 try-catch 分別處理每個查詢
    try {
        // 先分別測試兩個查詢
        $sql_camps = "
            SELECT COUNT(*) as camp_count
            FROM camp_applications c
            WHERE c.status = 0";
        
        $sql_articles = "
            SELECT COUNT(*) as article_count
            FROM articles ar
            WHERE ar.status = 1";
        
        // 記錄查詢結果
        $camp_count = $db->query($sql_camps)->fetchColumn();
        $article_count = $db->query($sql_articles)->fetchColumn();
        
        error_log("Debug - Pending camps count: " . $camp_count);
        error_log("Debug - Active articles count: " . $article_count);

        // 主要活動記錄查詢，添加 COLLATE 來統一字符集
        $sql_activities = "
            SELECT * FROM (
                SELECT 
                    'camp_review' COLLATE utf8mb4_unicode_ci as type,
                    CONCAT('營地審核: ', c.name) COLLATE utf8mb4_unicode_ci as description,
                    CONCAT('申請者: ', COALESCE(u.name, c.owner_name)) COLLATE utf8mb4_unicode_ci as detail,
                    c.created_at as time,
                    '系統' COLLATE utf8mb4_unicode_ci as admin_name
                FROM camp_applications c
                LEFT JOIN users u ON c.owner_id = u.id
                WHERE c.status = 0
                
                UNION ALL
                
                SELECT 
                    'article' COLLATE utf8mb4_unicode_ci as type,
                    CONCAT('文章管理: ', ar.title) COLLATE utf8mb4_unicode_ci as description,
                    CONCAT('分類: ', ac.name) COLLATE utf8mb4_unicode_ci as detail,
                    ar.created_at as time,
                    COALESCE(adm.name, '系統') COLLATE utf8mb4_unicode_ci as admin_name
                FROM articles ar
                JOIN article_categories ac ON ar.article_category = ac.id
                LEFT JOIN admins adm ON ar.created_by = adm.id
                WHERE ar.status = 1
            ) as combined_activities
            ORDER BY time DESC
            LIMIT 10";

        $result_activities = $db->query($sql_activities);
        
        if ($result_activities === false) {
            error_log("Debug - Query failed: " . $db->errorInfo()[2]);
            $activities = [];
        } else {
            $activities = $result_activities->fetchAll(PDO::FETCH_ASSOC);
            error_log("Debug - Total activities found: " . count($activities));
            
            // 輸出前幾筆資料的內容以供調試
            foreach (array_slice($activities, 0, 3) as $index => $activity) {
                error_log("Debug - Activity " . $index . ": " . json_encode($activity, JSON_UNESCAPED_UNICODE));
            }
        }
        
        $stats['activities'] = $activities;
        
        // 添加調試信息
        if (empty($activities)) {
            $stats['debug_info'] = [
                'camp_count' => $camp_count,
                'article_count' => $article_count,
                'sql_query' => $sql_activities
            ];
        }

    } catch (PDOException $e) {
        error_log("Activities Query Error: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        $stats['activities'] = [];
        $stats['debug_info'] = [
            'error' => $e->getMessage(),
            'sql_state' => $e->getCode()
        ];
    }

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