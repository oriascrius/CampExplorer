<?php
// 錯誤報告
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 引入資料庫連接
require_once __DIR__ . '/../../../camping_db.php';
global $db; // 使用正確的變數名稱

header('Content-Type: application/json');

try {
    session_start();
    
    error_log('=== API 訪問開始 ===');
    error_log('Session ID: ' . session_id());
    error_log('Session 數據: ' . print_r($_SESSION, true));
    
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('尚未登入，請先登入系統');
    }

    $owner_id = $_SESSION['owner_id'];
    error_log('Current owner_id: ' . $owner_id);
    
    // 修改為 LEFT JOIN 的查詢
    $sql = "SELECT 
                b.booking_id,
                b.quantity,
                b.total_price,
                b.status,
                b.booking_date,
                b.created_at,
                b.updated_at,
                sa.activity_name,
                u.name as user_name,
                csa.name as spot_name,
                aso.price as unit_price
            FROM bookings b
            LEFT JOIN activity_spot_options aso ON b.option_id = aso.option_id
            LEFT JOIN spot_activities sa ON aso.activity_id = sa.activity_id
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN camp_spot_applications csa ON aso.spot_id = csa.spot_id
            LEFT JOIN camp_applications ca ON csa.application_id = ca.application_id";
            // 暫時註釋掉 WHERE 條件，看看是否有數據
            // WHERE ca.owner_id = :owner_id

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Found bookings: ' . print_r($bookings, true));

    // 統計數據的查詢也要相應修改
    $stats_sql = "SELECT 
                    COUNT(DISTINCT b.booking_id) as total_bookings,
                    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                    COALESCE(SUM(b.total_price), 0) as total_revenue
                FROM bookings b
                LEFT JOIN activity_spot_options aso ON b.option_id = aso.option_id
                LEFT JOIN spot_activities sa ON aso.activity_id = sa.activity_id
                LEFT JOIN camp_applications ca ON sa.application_id = ca.application_id
                WHERE ca.owner_id = :owner_id";

    $stats_stmt = $db->prepare($stats_sql);
    $stats_stmt->execute(['owner_id' => $owner_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // 在前端顯示時處理 null 值
    foreach ($bookings as &$booking) {
        $booking['activity_name'] = $booking['activity_name'] ?? '未指定活動';
        $booking['spot_name'] = $booking['spot_name'] ?? '未指定營位';
        $booking['user_name'] = $booking['user_name'] ?? '未知用戶';
        $booking['unit_price'] = $booking['unit_price'] ?? 0;
    }

    $response = [
        'success' => true,
        'bookings' => $bookings,
        'stats' => [
            'total_bookings' => (int)$stats['total_bookings'],
            'pending_bookings' => (int)$stats['pending_bookings'],
            'total_revenue' => number_format($stats['total_revenue'], 0)
        ]
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log('API 錯誤: ' . $e->getMessage());
    error_log('錯誤追蹤: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'time' => date('Y-m-d H:i:s'),
            'session_status' => session_status(),
            'session_id' => session_id(),
            'db_status' => isset($db) ? 'connected' : 'not connected'
        ]
    ]);
}