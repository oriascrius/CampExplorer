<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $owner_id = $_SESSION['owner_id'];
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

    $sql = "WITH RECURSIVE dates AS (
        SELECT CURDATE() - INTERVAL :days DAY as date
        UNION ALL
        SELECT date + INTERVAL 1 DAY
        FROM dates
        WHERE date < CURDATE()
    )
    SELECT 
        dates.date,
        COUNT(CASE WHEN b.status = 'confirmed' THEN b.booking_id END) as order_count
    FROM dates
    LEFT JOIN bookings b ON DATE(b.booking_date) = dates.date
    LEFT JOIN activity_spot_options aso ON b.option_id = aso.option_id
    JOIN spot_activities sa ON aso.activity_id = sa.activity_id
    WHERE sa.owner_id = :owner_id
    GROUP BY dates.date
    ORDER BY dates.date";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':owner_id' => $owner_id,
        ':days' => $days
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $dates = [];
    $orders = [];
    foreach ($results as $row) {
        $dates[] = $row['date'];
        $orders[] = (int)$row['order_count'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'dates' => $dates,
            'orders' => $orders
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}