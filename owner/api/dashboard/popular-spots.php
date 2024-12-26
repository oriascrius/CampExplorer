<?php
session_start();
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $owner_id = $_SESSION['owner_id'];
    $period = isset($_GET['period']) ? $_GET['period'] : 'week';
    
    $interval = $period === 'week' ? '7 DAY' : '30 DAY';

    $sql = "SELECT 
        aso.spot_id as name,
        COUNT(CASE WHEN b.status = 'confirmed' THEN b.booking_id END) as bookings,
        COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END), 0) as revenue,
        0 as rating
        FROM activity_spot_options aso
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        LEFT JOIN bookings b ON aso.option_id = b.option_id 
            AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL $interval)
        WHERE sa.owner_id = :owner_id
        GROUP BY aso.spot_id
        HAVING bookings > 0
        ORDER BY bookings DESC, revenue DESC
        LIMIT 5";

    $stmt = $db->prepare($sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}