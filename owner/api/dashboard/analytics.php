<?php
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    require_once __DIR__ . '/../../../camping_db.php';

    // 訂單統計
    $order_sql = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN DATE(b.created_at) = CURDATE() THEN 1 END) as today_orders,
        COUNT(CASE WHEN b.status = 'confirmed' THEN 1 END) as confirmed_orders,
        COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled_orders,
        COALESCE(SUM(b.total_price), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN DATE(b.created_at) = CURDATE() THEN b.total_price ELSE 0 END), 0) as today_revenue,
        COALESCE(SUM(CASE WHEN DATE(b.created_at) >= DATE_FORMAT(NOW() ,'%Y-%m-01') THEN b.total_price ELSE 0 END), 0) as monthly_revenue
    FROM bookings b 
    JOIN activity_spot_options aso ON b.option_id = aso.option_id 
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id 
    WHERE sa.owner_id = :owner_id";

    $stmt = $db->prepare($order_sql);
    $stmt->execute([':owner_id' => $_SESSION['owner_id']]);
    $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 客戶統計
    $customer_sql = "SELECT 
        COUNT(DISTINCT b.user_id) as total_customers,
        COUNT(DISTINCT CASE WHEN user_bookings.booking_count > 1 THEN b.user_id END) as returning_customers
    FROM bookings b
    JOIN activity_spot_options aso ON b.option_id = aso.option_id 
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id 
    LEFT JOIN (
        SELECT user_id, COUNT(*) as booking_count
        FROM bookings
        GROUP BY user_id
    ) user_bookings ON b.user_id = user_bookings.user_id
    WHERE sa.owner_id = :owner_id";

    $stmt = $db->prepare($customer_sql);
    $stmt->execute([':owner_id' => $_SESSION['owner_id']]);
    $customer_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 營位統計
    $spot_sql = "SELECT 
        COUNT(DISTINCT aso.spot_id) as total_spots,
        COUNT(DISTINCT CASE WHEN sa.is_active = 1 THEN aso.spot_id END) as active_spots,
        COALESCE(SUM(aso.max_quantity), 0) as total_capacity,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.quantity ELSE 0 END), 0) as occupied_capacity
    FROM activity_spot_options aso
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    LEFT JOIN bookings b ON aso.option_id = b.option_id
    WHERE sa.owner_id = :owner_id";

    $stmt = $db->prepare($spot_sql);
    $stmt->execute([':owner_id' => $_SESSION['owner_id']]);
    $spot_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 計算上月營收（用於環比增長）
    $last_month_sql = "SELECT 
        COALESCE(SUM(b.total_price), 0) as last_month_revenue
    FROM bookings b 
    JOIN activity_spot_options aso ON b.option_id = aso.option_id 
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id 
    WHERE sa.owner_id = :owner_id
    AND DATE(b.created_at) >= DATE_SUB(DATE_FORMAT(NOW() ,'%Y-%m-01'), INTERVAL 1 MONTH)
    AND DATE(b.created_at) < DATE_FORMAT(NOW() ,'%Y-%m-01')";

    $stmt = $db->prepare($last_month_sql);
    $stmt->execute([':owner_id' => $_SESSION['owner_id']]);
    $last_month = $stmt->fetch(PDO::FETCH_ASSOC);

    // 計算環比增長
    $growth_rate = 0;
    if ($last_month['last_month_revenue'] > 0) {
        $growth_rate = (($order_stats['monthly_revenue'] - $last_month['last_month_revenue']) / $last_month['last_month_revenue']) * 100;
    }

    $overview = [
        'orders' => [
            'total' => (int)$order_stats['total_orders'],
            'today' => (int)$order_stats['today_orders'],
            'confirmed' => (int)$order_stats['confirmed_orders'],
            'pending' => (int)$order_stats['pending_orders'],
            'cancelled' => (int)$order_stats['cancelled_orders']
        ],
        'revenue' => [
            'total' => (float)$order_stats['total_revenue'],
            'today' => (float)$order_stats['today_revenue'],
            'monthly' => (float)$order_stats['monthly_revenue'],
            'growth_rate' => round($growth_rate, 1)
        ],
        'customers' => [
            'total' => (int)$customer_stats['total_customers'],
            'returning' => (int)$customer_stats['returning_customers'],
            'retention_rate' => $customer_stats['total_customers'] > 0 ? 
                round(($customer_stats['returning_customers'] / $customer_stats['total_customers']) * 100, 1) : 0
        ],
        'spots' => [
            'total' => (int)$spot_stats['total_spots'],
            'active' => (int)$spot_stats['active_spots'],
            'occupancy_rate' => $spot_stats['total_capacity'] > 0 ? 
                round(($spot_stats['occupied_capacity'] / $spot_stats['total_capacity']) * 100, 1) : 0
        ]
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => $overview
        ]
    ]);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取數據失敗',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
