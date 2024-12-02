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
        COUNT(DISTINCT aso.option_id) as total_spots,
        COUNT(DISTINCT CASE 
            WHEN b.status = 'confirmed' 
            AND b.check_in_date <= CURRENT_DATE 
            AND b.check_out_date >= CURRENT_DATE 
            THEN aso.option_id 
        END) as occupied_spots,
        COUNT(DISTINCT CASE 
            WHEN m.status = 'maintenance' 
            AND m.end_date >= CURRENT_DATE 
            THEN aso.option_id 
        END) as maintenance_spots,
        ROUND(AVG(aso.price), 0) as avg_price,
        ROUND(AVG(r.rating), 1) as avg_rating
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    LEFT JOIN maintenance m ON aso.option_id = m.spot_id
    LEFT JOIN reviews r ON b.booking_id = r.booking_id
    WHERE sa.owner_id = :owner_id";

    // 2. 各營位詳細狀態
    $spots_sql = "SELECT 
        aso.spot_name,
        aso.price,
        aso.capacity,
        CASE 
            WHEN m.status = 'maintenance' THEN '維護中'
            WHEN b.status = 'confirmed' THEN '使用中'
            ELSE '可預訂'
        END as current_status,
        COUNT(DISTINCT b.booking_id) as total_bookings,
        ROUND(AVG(r.rating), 1) as avg_rating,
        COUNT(DISTINCT r.review_id) as review_count,
        COALESCE(SUM(b.total_price), 0) as total_revenue,
        m.end_date as maintenance_end_date
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
        AND b.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    LEFT JOIN maintenance m ON aso.option_id = m.spot_id
        AND m.end_date >= CURRENT_DATE
    LEFT JOIN reviews r ON b.booking_id = r.booking_id
    WHERE sa.owner_id = :owner_id
    GROUP BY aso.spot_name, aso.price, aso.capacity
    ORDER BY total_revenue DESC";

    // 執行查詢
    $stmt = $db->prepare($overview_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $overview = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare($spots_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回結果
    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => [
                'total_spots' => $overview['total_spots'],
                'occupied_spots' => $overview['occupied_spots'],
                'maintenance_spots' => $overview['maintenance_spots'],
                'avg_price' => $overview['avg_price'],
                'avg_rating' => $overview['avg_rating'],
                'available_spots' => $overview['total_spots'] - $overview['occupied_spots'] - $overview['maintenance_spots']
            ],
            'spots' => array_map(function($spot) {
                return [
                    'name' => $spot['spot_name'],
                    'price' => (float)$spot['price'],
                    'capacity' => (int)$spot['capacity'],
                    'status' => $spot['current_status'],
                    'bookings' => (int)$spot['total_bookings'],
                    'rating' => (float)$spot['avg_rating'],
                    'reviews' => (int)$spot['review_count'],
                    'revenue' => (float)$spot['total_revenue'],
                    'maintenance_end' => $spot['maintenance_end_date']
                ];
            }, $spots)
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