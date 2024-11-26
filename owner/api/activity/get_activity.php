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

    if (!isset($_GET['id'])) {
        throw new Exception('未提供活動ID');
    }

    // 1. 獲取活動基本資料
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
                sa.application_id,
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
            AND sa.activity_id = :activity_id
            GROUP BY sa.activity_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'owner_id' => $_SESSION['owner_id'],
        'activity_id' => $_GET['id']
    ]);
    
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        throw new Exception('找不到該活動');
    }

    // 格式化日期
    $activity['start_date'] = date('Y-m-d', strtotime($activity['start_date']));
    $activity['end_date'] = date('Y-m-d', strtotime($activity['end_date']));

    // 2. 獲取活動營位選項
    $spotOptionsSql = "
        SELECT 
            aso.spot_id,
            aso.price,
            aso.max_quantity,
            aso.sort_order,
            csa.name as spot_name,
            csa.capacity as max_capacity
        FROM activity_spot_options aso
        JOIN camp_spot_applications csa ON aso.spot_id = csa.spot_id
        WHERE aso.activity_id = ?
        ORDER BY aso.sort_order";
    
    $stmt = $db->prepare($spotOptionsSql);
    $stmt->execute([$_GET['id']]);
    $spotOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. 獲取該營地所有可用營位
    $availableSpotsSql = "
        SELECT 
            csa.spot_id,
            csa.name as spot_name,
            csa.capacity as max_capacity
        FROM camp_spot_applications csa
        JOIN camp_applications ca ON csa.application_id = ca.application_id
        WHERE ca.application_id = ?
        AND ca.owner_id = ?
        AND ca.status = 1
        AND ca.operation_status = 1
        ORDER BY csa.name";
    
    $stmt = $db->prepare($availableSpotsSql);
    $stmt->execute([$activity['application_id'], $_SESSION['owner_id']]);
    $availableSpots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'activity' => $activity,
            'spot_options' => $spotOptions,
            'available_spots' => $availableSpots
        ]
    ]);

} catch (Exception $e) {
    error_log("Activity Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}