<?php
require_once __DIR__ . '/../../../camping_db.php';

// 開啟錯誤報告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 確保用戶已登入
session_start();
if (!isset($_SESSION['owner_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '請先登入'
    ]);
    exit;
}

$period = $_GET['period'] ?? 'week';

function getPeriodCondition($period) {
    switch($period) {
        case 'week':
            return ">= DATE_SUB(CURRENT_DATE(), INTERVAL 1 WEEK)";
        case 'month':
            return ">= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
        case 'year':
            return ">= DATE_SUB(CURRENT_DATE(), INTERVAL 1 YEAR)";
        default:
            return ">= DATE_SUB(CURRENT_DATE(), INTERVAL 1 WEEK)";
    }
}

try {
    $period_condition = getPeriodCondition($period);
    
    // 簡化查詢，先確認基本功能
    $query = "
        SELECT 
            csa.spot_id,
            csa.spot_name,
            COALESCE(COUNT(b.booking_id), 0) as booking_count,
            COALESCE(SUM(b.total_price), 0) as total_revenue
        FROM camp_spot_applications csa
        LEFT JOIN activity_spot_options aso ON csa.spot_id = aso.spot_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id 
        WHERE csa.application_id IN (
            SELECT application_id 
            FROM camp_applications 
            WHERE owner_id = :owner_id
        )
        GROUP BY csa.spot_id, csa.spot_name
        ORDER BY booking_count DESC, total_revenue DESC
        LIMIT 5
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':owner_id', $_SESSION['owner_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 調試信息
    error_log("Query executed for owner_id: " . $_SESSION['owner_id']);
    error_log("Results: " . print_r($spots, true));

    echo json_encode([
        'success' => true,
        'data' => $spots,
        'debug' => [
            'owner_id' => $_SESSION['owner_id'],
            'period' => $period,
            'count' => count($spots)
        ]
    ]);

} catch (Exception $e) {
    error_log("Popular spots error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取熱門營位數據失敗',
        'debug' => [
            'error' => $e->getMessage(),
            'owner_id' => $_SESSION['owner_id'] ?? 'not set'
        ]
    ]);
}