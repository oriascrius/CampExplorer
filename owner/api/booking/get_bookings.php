<?php
// 錯誤報告
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../camping_db.php';
global $db;

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
    
    // 修改 SQL 查詢，加入 WHERE 條件
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
            JOIN activity_spot_options aso ON b.option_id = aso.option_id
            JOIN spot_activities sa ON aso.activity_id = sa.activity_id
            JOIN users u ON b.user_id = u.id
            JOIN camp_spot_applications csa ON aso.spot_id = csa.spot_id
            JOIN camp_applications ca ON csa.application_id = ca.application_id
            WHERE sa.owner_id = :owner_id
            ORDER BY b.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute(['owner_id' => $owner_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Found bookings: ' . count($bookings));

    // 統計數據查詢
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    COALESCE(SUM(b.total_price), 0) as total_revenue
                FROM bookings b
                JOIN activity_spot_options aso ON b.option_id = aso.option_id
                JOIN spot_activities sa ON aso.activity_id = sa.activity_id
                WHERE sa.owner_id = :owner_id";

    $stats_stmt = $db->prepare($stats_sql);
    $stats_stmt->execute(['owner_id' => $owner_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // 格式化數據
    foreach ($bookings as &$booking) {
        $booking['booking_date'] = date('Y-m-d H:i', strtotime($booking['booking_date']));
        $booking['created_at'] = date('Y-m-d H:i', strtotime($booking['created_at']));
        $booking['total_price'] = number_format($booking['total_price'], 0);
        $booking['unit_price'] = number_format($booking['unit_price'], 0);
    }

    $response = [
        'success' => true,
        'bookings' => $bookings,
        'stats' => [
            'total' => (int)$stats['total'],
            'pending' => (int)$stats['pending'],
            'confirmed' => (int)$stats['confirmed'],
            'cancelled' => (int)$stats['cancelled'],
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
            'owner_id' => $_SESSION['owner_id'] ?? 'not set'
        ]
    ]);
}