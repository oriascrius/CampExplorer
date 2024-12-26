<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['owner_id'])) {
    error_log('Session not found: ' . print_r($_SESSION, true));
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

$owner_id = $_SESSION['owner_id'];
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

try {
    error_log("Revenue trend request received for owner_id: {$owner_id}, days: {$days}");
    
    $sql = "WITH RECURSIVE date_range AS (
        SELECT CURDATE() - INTERVAL :days DAY as date
        UNION ALL
        SELECT date + INTERVAL 1 DAY
        FROM date_range
        WHERE date < CURDATE()
    )
    SELECT 
        date_range.date,
        COALESCE(SUM(CASE 
            WHEN b.status = 'confirmed' 
            THEN b.total_price 
            ELSE 0 
        END), 0) as revenue
    FROM date_range
    LEFT JOIN bookings b ON DATE(b.created_at) = date_range.date
    LEFT JOIN activity_spot_options aso ON b.option_id = aso.option_id
    LEFT JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        AND sa.owner_id = :owner_id
    GROUP BY date_range.date
    ORDER BY date_range.date ASC";
    
    error_log("Executing SQL query: " . $sql);
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':owner_id' => $owner_id,
        ':days' => $days
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Query results: " . print_r($results, true));
    
    // 處理數據格式
    $dates = [];
    $revenues = [];
    
    foreach ($results as $row) {
        $dates[] = date('m/d', strtotime($row['date']));
        $revenues[] = round((float)$row['revenue'], 2); // 四捨五入到小數點後兩位
    }

    $response = [
        'success' => true,
        'data' => [
            'dates' => $dates,
            'revenues' => $revenues
        ]
    ];
    error_log("Revenue trend data: " . json_encode($response));
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Revenue trend error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '獲取營收趨勢數據失敗'
    ]);
}