<?php
require_once __DIR__ . '/../../../camping_db.php';

// 開啟錯誤報告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 檢查 session
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['owner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$period = isset($_GET['period']) ? intval($_GET['period']) : 7;
$owner_id = $_SESSION['owner_id'];

try {
    // 先檢查營主是否有任何已通過的營地
    $check_query = "
        SELECT COUNT(*) as camp_count 
        FROM camp_applications 
        WHERE owner_id = :owner_id AND status = 1
    ";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $camp_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['camp_count'];
    
    error_log("Camp count for owner $owner_id: $camp_count");

    if ($camp_count == 0) {
        echo json_encode([
            'success' => true,
            'data' => [
                'dates' => array_map(function($i) {
                    return date('m/d', strtotime("-$i days"));
                }, range($period - 1, 0)),
                'orders' => array_fill(0, $period, 0),
                'revenue' => array_fill(0, $period, 0)
            ]
        ]);
        exit;
    }

    // 主要查詢
    $query = "
        SELECT 
            DATE(b.created_at) as date,
            COUNT(DISTINCT b.booking_id) as order_count,
            COALESCE(SUM(b.total_price), 0) as daily_revenue
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        WHERE aso.application_id IN (
            SELECT application_id 
            FROM camp_applications 
            WHERE owner_id = :owner_id 
            AND status = 1
        )
        AND b.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
        AND b.status != 'cancelled'
        GROUP BY DATE(b.created_at)
        ORDER BY date ASC
    ";

    error_log("Executing query with params - owner_id: $owner_id, period: $period");
    error_log("SQL Query: " . $query);

    $stmt = $db->prepare($query);
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
    $stmt->bindParam(':period', $period, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Query results: " . print_r($results, true));

    // 生成日期範圍
    $dates = [];
    $orders = [];
    $revenue = [];
    
    // 創建完整的日期範圍
    for ($i = $period - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('m/d', strtotime($date));
        $found = false;
        
        foreach ($results as $row) {
            if ($row['date'] == $date) {
                $orders[] = intval($row['order_count']);
                $revenue[] = floatval($row['daily_revenue']);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $orders[] = 0;
            $revenue[] = 0;
        }
    }

    $response = [
        'success' => true,
        'data' => [
            'dates' => $dates,
            'orders' => $orders,
            'revenue' => $revenue
        ]
    ];

    error_log("Final response: " . json_encode($response));
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '資料庫連接錯誤',
        'debug' => [
            'error' => $e->getMessage(),
            'owner_id' => $owner_id,
            'period' => $period
        ]
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '無法載入訂單趨勢數據',
        'debug' => [
            'error' => $e->getMessage(),
            'owner_id' => $owner_id,
            'period' => $period
        ]
    ]);
}