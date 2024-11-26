<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    $sql = "SELECT 
                sa.activity_id,
                sa.activity_name,
                sa.title,
                sa.subtitle,
                sa.description,
                sa.notice,
                sa.start_date,
                sa.end_date,
                sa.is_active,
                sa.main_image,
                ca.name as camp_name,
                MIN(aso.price) as min_price,
                MAX(aso.price) as max_price,
                SUM(aso.max_quantity) as total_quantity,
                GROUP_CONCAT(DISTINCT csa.name ORDER BY csa.name) as spot_names,
                COUNT(DISTINCT aso.spot_id) as spot_count,
                CASE 
                    WHEN ca.status = 0 THEN '營地審核中'
                    WHEN ca.status = 2 THEN '營地未通過'
                    WHEN sa.is_active = 0 THEN '下架中'
                    WHEN sa.is_active = 1 AND sa.end_date < CURDATE() THEN '已結束'
                    WHEN sa.is_active = 1 AND sa.start_date > CURDATE() THEN '即將開始'
                    WHEN sa.is_active = 1 AND sa.start_date <= CURDATE() AND sa.end_date >= CURDATE() THEN '進行中'
                END as activity_status
            FROM spot_activities sa
            JOIN camp_applications ca ON sa.application_id = ca.application_id
            LEFT JOIN activity_spot_options aso ON sa.activity_id = aso.activity_id
            LEFT JOIN camp_spot_applications csa ON aso.spot_id = csa.spot_id
            WHERE sa.owner_id = :owner_id
            GROUP BY sa.activity_id
            ORDER BY sa.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute(['owner_id' => $_SESSION['owner_id']]);
    $activities = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);

} catch (Exception $e) {
    error_log("Activity Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}