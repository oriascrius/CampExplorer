<?php
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

function getDashboardStats($owner_id) {
    global $db;
    
    try {
        // 基本訂單統計
        $orders_sql = "SELECT 
            COUNT(CASE WHEN DATE(b.created_at) = CURDATE() THEN 1 END) as today_count,
            COUNT(CASE WHEN MONTH(b.created_at) = MONTH(CURRENT_DATE()) 
                      AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN 1 END) as month_count,
            COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_count,
            COALESCE(SUM(CASE WHEN DATE(b.created_at) = CURDATE() THEN b.total_price ELSE 0 END), 0) as today_revenue,
            COALESCE(SUM(CASE WHEN MONTH(b.created_at) = MONTH(CURRENT_DATE()) 
                              AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) 
                              THEN b.total_price ELSE 0 END), 0) as month_revenue
        FROM camp_applications ca
        JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id
        WHERE ca.owner_id = ? AND ca.status = 1";
        
        $orders_stmt = $db->prepare($orders_sql);
        $orders_stmt->execute([$owner_id]);
        $orders_result = $orders_stmt->fetch(PDO::FETCH_ASSOC);

        // 訂單趨勢（最近7天）
        $trends_sql = "SELECT 
            dates.date,
            COALESCE(COUNT(DISTINCT b.booking_id), 0) as order_count
        FROM (
            SELECT CURDATE() - INTERVAL (a.a) DAY as date
            FROM (
                SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
            ) as a
        ) dates
        LEFT JOIN camp_applications ca ON ca.owner_id = ? AND ca.status = 1
        LEFT JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        LEFT JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id 
            AND DATE(b.created_at) = dates.date
        GROUP BY dates.date
        ORDER BY dates.date";
        
        $trends_stmt = $db->prepare($trends_sql);
        $trends_stmt->execute([$owner_id]);
        $trends_result = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 整理趨勢數據
        $trends_dates = [];
        $trends_orders = [];
        foreach ($trends_result as $row) {
            $trends_dates[] = date('m/d', strtotime($row['date']));
            $trends_orders[] = (int)$row['order_count'];
        }

        // 營位使用率
        $spots_sql = "SELECT 
            COUNT(DISTINCT csa.spot_id) as total_spots,
            COUNT(DISTINCT CASE WHEN b.status = 'confirmed' 
                  AND b.booking_date >= CURDATE() THEN b.booking_id END) as booked
        FROM camp_applications ca
        JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        LEFT JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id
        WHERE ca.owner_id = ? AND ca.status = 1";
        
        $spots_stmt = $db->prepare($spots_sql);
        $spots_stmt->execute([$owner_id]);
        $spots_result = $spots_stmt->fetch(PDO::FETCH_ASSOC);

        // 熱門營位排行
        $popular_spots_sql = "SELECT 
            csa.name as spot_name,
            COUNT(DISTINCT b.booking_id) as booking_count
        FROM camp_applications ca
        JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        LEFT JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id
        WHERE ca.owner_id = ? 
        AND ca.status = 1
        GROUP BY csa.spot_id, csa.name
        ORDER BY booking_count DESC
        LIMIT 5";
        
        $popular_spots_stmt = $db->prepare($popular_spots_sql);
        $popular_spots_stmt->execute([$owner_id]);
        $popular_spots_result = $popular_spots_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 營地基本信息
        $camp_sql = "SELECT 
            ca.application_id,
            ca.status,
            ca.created_at,
            ca.operation_status,
            COUNT(csa.spot_id) as spots_count
        FROM camp_applications ca
        LEFT JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        WHERE ca.owner_id = ?
        GROUP BY ca.application_id, ca.status, ca.created_at, ca.operation_status
        ORDER BY ca.created_at DESC 
        LIMIT 1";
        
        $camp_stmt = $db->prepare($camp_sql);
        $camp_stmt->execute([$owner_id]);
        $camp_result = $camp_stmt->fetch(PDO::FETCH_ASSOC);

        // 月度營收查詢
        $monthly_revenue_sql = "SELECT 
            DATE_FORMAT(b.created_at, '%Y-%m') as month,
            COALESCE(SUM(b.total_price), 0) as revenue
        FROM camp_applications ca
        JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id
        WHERE ca.owner_id = ? 
        AND ca.status = 1
        AND b.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(b.created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6";

        $monthly_revenue_stmt = $db->prepare($monthly_revenue_sql);
        $monthly_revenue_stmt->execute([$owner_id]);
        $monthly_revenue_result = $monthly_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 整理最終返回的數據結構
        $stats = [
            'orders' => [
                'today' => (int)($orders_result['today_count'] ?? 0),
                'month' => (int)($orders_result['month_count'] ?? 0),
                'pending' => (int)($orders_result['pending_count'] ?? 0),
                'revenue' => [
                    'today' => (float)($orders_result['today_revenue'] ?? 0),
                    'month' => (float)($orders_result['month_revenue'] ?? 0)
                ]
            ],
            'spots' => [
                'total' => (int)($spots_result['total_spots'] ?? 0),
                'booked' => (int)($spots_result['booked'] ?? 0),
                'available' => (int)(($spots_result['total_spots'] ?? 0) - ($spots_result['booked'] ?? 0))
            ],
            'trends' => [
                'dates' => $trends_dates ?? [],
                'orders' => $trends_orders ?? []
            ],
            'popular_spots' => array_map(function($spot) {
                return [
                    'name' => $spot['spot_name'],
                    'bookings' => (int)($spot['booking_count'] ?? 0)
                ];
            }, $popular_spots_result ?? []),
            'monthly_revenue' => array_map(function($item) {
                return [
                    'month' => date('n月', strtotime($item['month'] . '-01')),
                    'revenue' => (float)$item['revenue']
                ];
            }, array_reverse($monthly_revenue_result) ?? [])
        ];

        return ['success' => true, 'data' => $stats];
        
    } catch (PDOException $e) {
        error_log("Dashboard Stats Error: " . $e->getMessage());
        return ['success' => false, 'error' => '獲取數據失敗'];
    }
}

// 處理API請求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_start();
    if (!isset($_SESSION['owner_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => '未授權訪問']);
        exit;
    }

    $result = getDashboardStats($_SESSION['owner_id']);
    echo json_encode($result);
}
?>