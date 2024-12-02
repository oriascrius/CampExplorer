<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $owner_id = $_SESSION['owner_id'];

    // 1. 營地整體統計
    $overview_sql = "SELECT 
        COUNT(DISTINCT aso.option_id) as total_spots,
        COUNT(DISTINCT CASE WHEN b.status = 'confirmed' AND b.check_in_date <= CURRENT_DATE AND b.check_out_date >= CURRENT_DATE THEN aso.option_id END) as occupied_spots,
        COUNT(DISTINCT CASE WHEN m.end_date >= CURRENT_DATE THEN aso.option_id END) as maintenance_spots,
        ROUND(AVG(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE NULL END), 0) as avg_spot_price,
        COUNT(DISTINCT CASE WHEN r.rating IS NOT NULL THEN aso.option_id END) as rated_spots,
        ROUND(AVG(r.rating), 1) as avg_rating
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    LEFT JOIN maintenance m ON aso.option_id = m.spot_id
    LEFT JOIN reviews r ON b.booking_id = r.booking_id
    WHERE sa.owner_id = :owner_id";

    // 2. 營位使用率分析
    $usage_sql = "SELECT 
        aso.spot_name,
        COUNT(DISTINCT CASE WHEN b.status = 'confirmed' THEN DATE(b.check_in_date) END) as booked_days,
        DATEDIFF(CURRENT_DATE, MIN(b.check_in_date)) + 1 as available_days,
        ROUND(COUNT(DISTINCT CASE WHEN b.status = 'confirmed' THEN b.booking_id END) * 100.0 / 
            NULLIF(DATEDIFF(CURRENT_DATE, MIN(b.check_in_date)) + 1, 0), 1) as occupancy_rate,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END), 0) as total_revenue,
        ROUND(AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating ELSE NULL END), 1) as avg_rating
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    LEFT JOIN reviews r ON b.booking_id = r.booking_id
    WHERE sa.owner_id = :owner_id
    GROUP BY aso.spot_name
    ORDER BY occupancy_rate DESC";

    // 執行查詢
    $stmt = $db->prepare($overview_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $overview = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare($usage_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $usage_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回結果
    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => $overview,
            'usage_stats' => $usage_stats
        ]
    ]);

} catch (Exception $e) {
    error_log("Camp analytics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取營地分析數據失敗',
        'debug' => $e->getMessage()
    ]);
}