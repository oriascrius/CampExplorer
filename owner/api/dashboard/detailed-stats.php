<?php
require_once __DIR__ . '/../../camping_db.php';

if (!isset($_SESSION['owner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

$owner_id = $_SESSION['owner_id'];

try {
    // 1. 訂單來源分析
    $sourcesSql = "
        SELECT 
            COALESCE(b.source, '直接預訂') as source,
            COUNT(*) as count,
            COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END), 0) as revenue
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = :owner_id
        AND b.status != 'cancelled'
        GROUP BY COALESCE(b.source, '直接預訂')
        ORDER BY count DESC";
    
    $stmt = $db->prepare($sourcesSql);
    $stmt->execute([':owner_id' => $owner_id]);
    $orderSources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. 營運效能指標
    $performanceSql = "
        SELECT 
            (COUNT(CASE WHEN b.status = 'confirmed' THEN 1 END) * 100.0 / 
                NULLIF(COUNT(*), 0)) as conversion_rate,
            (COUNT(DISTINCT CASE WHEN bc.booking_count > 1 THEN b.customer_id END) * 100.0 / 
                NULLIF(COUNT(DISTINCT b.customer_id), 0)) as retention_rate,
            AVG(b.total_price) as avg_order_value
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        LEFT JOIN (
            SELECT customer_id, COUNT(*) as booking_count
            FROM bookings
            GROUP BY customer_id
        ) bc ON b.customer_id = bc.customer_id
        WHERE sa.owner_id = :owner_id";
    
    $stmt = $db->prepare($performanceSql);
    $stmt->execute([':owner_id' => $owner_id]);
    $performance = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. 熱門營位排行
    $spotsSql = "
        SELECT 
            aso.spot_name,
            COUNT(b.booking_id) as booking_count,
            SUM(b.total_price) as total_revenue,
            (COUNT(b.booking_id) * 100.0 / 
                NULLIF(DATEDIFF(CURRENT_DATE, MIN(b.booking_date)) + 1, 0)) as occupancy_rate
        FROM activity_spot_options aso
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id
        WHERE sa.owner_id = :owner_id
        GROUP BY aso.spot_name
        ORDER BY booking_count DESC
        LIMIT 5";
    
    $stmt = $db->prepare($spotsSql);
    $stmt->execute([':owner_id' => $owner_id]);
    $popularSpots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. 客戶分析
    $customerSql = "
        SELECT 
            COUNT(DISTINCT b.customer_id) as unique_customers,
            AVG(booking_count) as avg_bookings_per_customer,
            MAX(booking_count) as max_bookings_by_customer,
            SUM(CASE WHEN booking_count > 1 THEN 1 ELSE 0 END) as returning_customers
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = :owner_id";

    $stmt = $db->prepare($customerSql);
    $stmt->execute([':owner_id' => $owner_id]);
    $customerAnalytics = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. 營位使用率分析
    $occupancySql = "
        SELECT 
            aso.spot_id as spot_name,
            COUNT(DISTINCT DATE(b.check_in_date)) as occupied_days,
            DATEDIFF(CURRENT_DATE, MIN(b.check_in_date)) + 1 as total_days,
            COUNT(DISTINCT b.booking_id) as total_bookings,
            SUM(b.total_price) as total_revenue
        FROM activity_spot_options aso
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id 
            AND b.status = 'confirmed'
        WHERE sa.owner_id = :owner_id
        GROUP BY aso.spot_id";

    $stmt = $db->prepare($occupancySql);
    $stmt->execute([':owner_id' => $owner_id]);
    $occupancyAnalytics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. 取消訂單分析
    $cancellationSql = "
        SELECT 
            COALESCE(cancellation_reason, '其他') as reason,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_time_to_cancel
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = :owner_id
        AND b.status = 'cancelled'
        GROUP BY cancellation_reason";

    $stmt = $db->prepare($cancellationSql);
    $stmt->execute([':owner_id' => $owner_id]);
    $cancellationAnalytics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 更新返回的數據
    echo json_encode([
        'success' => true,
        'data' => [
            'orderSources' => $orderSources,
            'performance' => $performance,
            'popularSpots' => $popularSpots,
            'customerAnalytics' => $customerAnalytics,
            'occupancyAnalytics' => $occupancyAnalytics,
            'cancellationAnalytics' => $cancellationAnalytics
        ]
    ]);

} catch (PDOException $e) {
    error_log("Detailed stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取詳細統計數據失敗'
    ]);
}