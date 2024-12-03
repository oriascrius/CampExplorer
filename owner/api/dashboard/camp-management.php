<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $owner_id = $_SESSION['owner_id'];
    
    // 1. 營地整體狀況
    $overview_sql = "SELECT 
        COUNT(DISTINCT csa.spot_id) as total_spots,
        COUNT(DISTINCT CASE 
            WHEN b.status = 'confirmed' 
            AND b.check_in_date <= CURRENT_DATE 
            AND b.check_out_date >= CURRENT_DATE 
            THEN csa.spot_id 
        END) as occupied_spots,
        COUNT(DISTINCT CASE 
            WHEN cr.status = 'maintenance' 
            AND cr.end_date >= CURRENT_DATE 
            THEN csa.spot_id
        END) as maintenance_spots,
        COUNT(DISTINCT CASE 
            WHEN b.status = 'pending' 
            THEN b.booking_id 
        END) as pending_bookings,
        SUM(CASE 
            WHEN DATE(b.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            AND b.status = 'confirmed'
            THEN b.total_price 
            ELSE 0 
        END) as monthly_revenue,
        SUM(CASE 
            WHEN b.status = 'confirmed' 
            THEN b.total_price 
            ELSE 0 
        END) as total_revenue
    FROM camp_spot_applications csa
    LEFT JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    LEFT JOIN campsite_reviews cr ON csa.application_id = cr.application_id
    WHERE csa.owner_id = ?";

    // 執行 overview 查詢
    $stmt = $conn->prepare($overview_sql);
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();
    $overview = $stmt->get_result()->fetch_assoc();

    // 檢查並設置預設值
    $overview = array_merge([
        'total_spots' => 0,
        'occupied_spots' => 0,
        'maintenance_spots' => 0,
        'pending_bookings' => 0,
        'monthly_revenue' => 0,
        'total_revenue' => 0
    ], $overview ?? []);

    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => [
                'total_spots' => (int)$overview['total_spots'],
                'occupied_spots' => (int)$overview['occupied_spots'],
                'maintenance_spots' => (int)$overview['maintenance_spots'],
                'pending_bookings' => (int)$overview['pending_bookings'],
                'monthly_revenue' => (float)$overview['monthly_revenue'],
                'total_revenue' => (float)$overview['total_revenue']
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Camp management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取營地管理數據失敗',
        'debug' => $e->getMessage()
    ]);
}