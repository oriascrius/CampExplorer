<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('請先登入');
    }

    $sql = "SELECT DISTINCT 
                ca.application_id, 
                ca.name as camp_name 
            FROM camp_applications ca
            WHERE ca.owner_id = :owner_id 
            AND ca.status = 1 
            AND ca.operation_status = 1
            ORDER BY ca.name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['owner_id' => $_SESSION['owner_id']]);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($camps) . " camps for owner_id: " . $_SESSION['owner_id']);

    echo json_encode([
        'success' => true,
        'camps' => $camps,
        'message' => empty($camps) ? '尚無已審核通過的營地' : null
    ]);

} catch (Exception $e) {
    error_log("Error in get_approved_camps.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}