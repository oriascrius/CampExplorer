<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $owner_id = $_SESSION['owner_id'];
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    // 1. 基本統計
    $basic_stats_sql = "SELECT 
        AVG(daily_revenue) as avg_daily_revenue,
        MAX(daily_revenue) as peak_revenue,
        MAX(CASE WHEN daily_revenue = peak_revenue THEN date END) as peak_revenue_date
    FROM (
        SELECT 
            DATE(b.created_at) as date,
            SUM(b.total_price) as daily_revenue
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = ? AND b.created_at BETWEEN ? AND ?
        GROUP BY DATE(b.created_at)
    ) as daily_stats";

    // 2. 營位使用率
    $occupancy_sql = "SELECT 
        ROUND(COUNT(DISTINCT b.booking_id) * 100.0 / 
            (DATEDIFF(?, ?) * (
                SELECT COUNT(DISTINCT aso2.option_id) 
                FROM activity_spot_options aso2 
                JOIN spot_activities sa2 ON aso2.activity_id = sa2.activity_id 
                WHERE sa2.owner_id = ?
            )), 2) as occupancy_rate
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = ? AND b.check_in_date BETWEEN ? AND ?
        AND b.status = 'confirmed'";

    // 3. 回頭客比例
    $return_customer_sql = "SELECT 
        ROUND(COUNT(DISTINCT CASE WHEN booking_count > 1 THEN user_id END) * 100.0 / 
            COUNT(DISTINCT user_id), 2) as return_rate
        FROM (
            SELECT user_id, COUNT(*) as booking_count
            FROM bookings b
            JOIN activity_spot_options aso ON b.option_id = aso.option_id
            JOIN spot_activities sa ON aso.activity_id = sa.activity_id
            WHERE sa.owner_id = ? AND created_at BETWEEN ? AND ?
            GROUP BY user_id
        ) as user_stats";

    // 4. 熱門營位排行
    $popular_spots_sql = "SELECT 
        aso.spot_id,
        sa.activity_name as spot_name,
        COUNT(CASE WHEN b.status = 'confirmed' THEN b.booking_id END) as bookings,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END), 0) as revenue,
        CAST(COALESCE(AVG(cr.status), 0) AS DECIMAL(3,1)) as rating
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id 
        AND b.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :interval DAY)
    LEFT JOIN campsite_reviews cr ON aso.application_id = cr.application_id
    WHERE sa.owner_id = :owner_id
    GROUP BY aso.spot_id, sa.activity_name
    HAVING bookings > 0
    ORDER BY bookings DESC, revenue DESC
    LIMIT 5";

    // 執行查詢
    $stmt = $db->prepare($basic_stats_sql);
    $stmt->execute([$owner_id, $start_date, $end_date]);
    $basic_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare($occupancy_sql);
    $stmt->execute([$end_date, $start_date, $owner_id, $owner_id, $start_date, $end_date]);
    $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare($return_customer_sql);
    $stmt->execute([$owner_id, $start_date, $end_date]);
    $return_rate = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare($popular_spots_sql);
    $stmt->execute([
        ':owner_id' => $owner_id,
        ':interval' => $period === 'week' ? 7 : 30
    ]);
    $popular_spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'avgDailyRevenue' => round($basic_stats['avg_daily_revenue'], 2),
            'peakRevenue' => $basic_stats['peak_revenue'],
            'peakRevenueDate' => $basic_stats['peak_revenue_date'],
            'occupancyRate' => $occupancy['occupancy_rate'],
            'returnCustomerRate' => $return_rate['return_rate'],
            'popularSpots' => $popular_spots
        ]
    ]);

} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取分析數據失敗'
    ]);
}