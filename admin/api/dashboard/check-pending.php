<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../camping_db.php';

try {
    $sql_pending = "SELECT 
        COUNT(CASE WHEN a.status = 0 THEN 1 END) as pending_camps,
        COUNT(CASE WHEN p.stock <= 10 THEN 1 END) as low_stock_products,
        COUNT(CASE WHEN d.status = 'pending' THEN 1 END) as pending_discussions
    FROM 
        (SELECT 0 as dummy) as dummy_table
        LEFT JOIN camp_applications a ON 1=1
        LEFT JOIN products p ON 1=1
        LEFT JOIN user_discussions d ON 1=1";

    $result = $db->query($sql_pending);
    $stats = $result->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($stats);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}