<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $owner_id = $_SESSION['owner_id'];

    // 1. 營位狀態概覽
    $status_sql = "SELECT 
        aso.option_id,
        aso.spot_id,
        sa.activity_name as spot_name,
        aso.price,
        aso.max_quantity as capacity,
        COUNT(DISTINCT CASE WHEN b.status = 'confirmed' THEN b.booking_id END) as bookings,
        CASE 
            WHEN b.status = 'confirmed' AND b.booking_date <= CURRENT_DATE THEN '使用中'
            ELSE '可預訂'
        END as status,
        CAST(COALESCE(AVG(cr.status), 0) AS DECIMAL(3,1)) as rating,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END), 0) as monthly_revenue
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id 
        AND b.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    LEFT JOIN campsite_reviews cr ON aso.application_id = cr.application_id
    WHERE sa.owner_id = :owner_id
    GROUP BY 
        aso.option_id, 
        aso.spot_id,
        sa.activity_name,
        aso.price, 
        aso.max_quantity";

    $stmt = $db->prepare($status_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 確保所有數值欄位都是數字類型
    $spots = array_map(function($spot) {
        return [
            'option_id' => (int)$spot['option_id'],
            'spot_id' => (int)$spot['spot_id'],
            'spot_name' => $spot['spot_name'],
            'price' => (float)$spot['price'],
            'capacity' => (int)$spot['capacity'],
            'bookings' => (int)$spot['bookings'],
            'status' => $spot['status'],
            'rating' => (float)$spot['rating'],
            'monthly_revenue' => (float)$spot['monthly_revenue']
        ];
    }, $spots);

    // 2. 計算概要統計
    $summary_sql = "SELECT 
        COUNT(DISTINCT aso.option_id) as total_spots,
        COUNT(DISTINCT CASE WHEN b.status = 'confirmed' AND b.booking_date <= CURRENT_DATE THEN aso.option_id END) as active_spots,
        ROUND(AVG(aso.price), 0) as avg_price,
        CAST(COALESCE(AVG(cr.status), 0) AS DECIMAL(3,1)) as avg_rating
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    LEFT JOIN campsite_reviews cr ON aso.application_id = cr.application_id
    WHERE sa.owner_id = :owner_id";

    $stmt = $db->prepare($summary_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'spots' => $spots,
            'summary' => $summary
        ]
    ]);

} catch (Exception $e) {
    error_log("Spot status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取營位狀態失敗'
    ]);
}