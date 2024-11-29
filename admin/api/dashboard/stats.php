<?php
require_once __DIR__ . "../../../../camping_db.php"; // 確保已載入資料庫連線

// 使用現有的 SQL 查詢
$sql_pending = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_camps,
    COUNT(CASE WHEN stock <= 10 THEN 1 END) as low_stock_products,
    COUNT(CASE WHEN reply_status = 'pending' THEN 1 END) as pending_discussions
FROM (
    SELECT 'camp' as type, status, NULL as stock, NULL as reply_status 
    FROM camp_applications
    UNION ALL
    SELECT 'product' as type, NULL as status, stock, NULL as reply_status 
    FROM products
    UNION ALL
    SELECT 'discussion' as type, NULL as status, NULL as stock, status as reply_status 
    FROM discussions
) combined_stats";

$result = $db->query($sql_pending);
$stats = $result->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($stats);