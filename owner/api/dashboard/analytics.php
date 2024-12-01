<?php
session_start();
error_log("Current directory: " . __DIR__);
error_log("Requested file: " . $_SERVER['REQUEST_URI']);

require_once __DIR__ . '/../../../camping_db.php';

// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // 檢查數據庫連接
    if (!isset($db)) {
        throw new Exception("Database connection is not initialized");
    }

    if (!$db instanceof PDO) {
        throw new Exception("Invalid database connection object");
    }

    if (!isset($_SESSION['owner_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '未登入']);
        exit;
    }

    $owner_id = $_SESSION['owner_id'];
    error_log("Processing request for owner_id: " . $owner_id);

    // 查詢訂單統計
    $bookings_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN DATE(booking_date) = CURDATE() THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        WHERE aso.application_id IN (
            SELECT application_id FROM camp_applications WHERE owner_id = :owner_id
        )";
    
    $stmt = $db->prepare($bookings_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 查詢營收統計
    $revenue_sql = "SELECT 
        COALESCE(SUM(total_price), 0) as total_revenue,
        COALESCE(SUM(CASE 
            WHEN DATE(b.created_at) = CURDATE() 
            THEN total_price 
            ELSE 0 
        END), 0) as today_revenue,
        COALESCE(SUM(CASE 
            WHEN MONTH(b.created_at) = MONTH(CURDATE()) 
            AND YEAR(b.created_at) = YEAR(CURDATE())
            THEN total_price 
            ELSE 0 
        END), 0) as monthly_revenue,
        COALESCE(SUM(CASE 
            WHEN MONTH(b.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND YEAR(b.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            THEN total_price 
            ELSE 0 
        END), 0) as last_month_revenue
    FROM bookings b
    JOIN activity_spot_options aso ON b.option_id = aso.option_id
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    WHERE sa.owner_id = :owner_id
    AND b.status = 'confirmed'";
    
    $stmt = $db->prepare($revenue_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $revenue_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 查詢客戶統計
    $customers_sql = "SELECT 
        COUNT(DISTINCT user_id) as total_customers,
        COUNT(DISTINCT CASE 
            WHEN booking_count > 1 THEN user_id 
        END) as returning_customers
    FROM (
        SELECT user_id, COUNT(*) as booking_count
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = :owner_id
        GROUP BY user_id
    ) customer_stats";

    $stmt = $db->prepare($customers_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $customer_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 查詢營位統計
    $spots_sql = "SELECT 
        COUNT(DISTINCT aso.option_id) as total_spots,
        COUNT(DISTINCT CASE 
            WHEN b.status = 'confirmed' 
            AND DATE(b.created_at) = CURDATE()
            THEN aso.option_id 
        END) as operating_spots
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    WHERE sa.owner_id = :owner_id";

    $stmt = $db->prepare($spots_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $spot_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 整理返回數據
    $stats = [
        'bookings' => [
            'total' => (int)$booking_stats['total'] ?? 0,
            'today' => (int)$booking_stats['today'] ?? 0,
            'confirmed' => (int)$booking_stats['confirmed'] ?? 0,
            'pending' => (int)$booking_stats['pending'] ?? 0,
            'cancelled' => (int)$booking_stats['cancelled'] ?? 0
        ],
        'revenue' => [
            'total' => (float)$revenue_stats['total_revenue'] ?? 0,
            'today' => (float)$revenue_stats['today_revenue'] ?? 0,
            'monthly' => (float)$revenue_stats['monthly_revenue'] ?? 0,
            'last_month' => (float)$revenue_stats['last_month_revenue'] ?? 0
        ],
        'customers' => [
            'total' => (int)$customer_stats['total_customers'] ?? 0,
            'returning' => (int)$customer_stats['returning_customers'] ?? 0
        ],
        'spots' => [
            'total' => (int)$spot_stats['total_spots'] ?? 0,
            'operating' => (int)$spot_stats['operating_spots'] ?? 0
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

    // 輸出 debug 信息
    error_log("Revenue stats: " . json_encode($revenue_stats));
    error_log("Spot stats: " . json_encode($spot_stats));

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '數據庫錯誤',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤',
        'debug' => $e->getMessage()
    ]);
}
