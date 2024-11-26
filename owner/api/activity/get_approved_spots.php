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

    if (!isset($_GET['application_id'])) {
        throw new Exception('缺少營地ID');
    }

    $sql = "SELECT 
                csa.spot_id,
                csa.name as spot_name,
                csa.capacity as max_capacity
            FROM camp_spot_applications csa
            JOIN camp_applications ca ON csa.application_id = ca.application_id
            WHERE ca.application_id = :application_id 
            AND ca.owner_id = :owner_id
            AND ca.status = 1
            AND ca.operation_status = 1
            ORDER BY csa.name";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'application_id' => $_GET['application_id'],
        'owner_id' => $_SESSION['owner_id']
    ]);
    $spots = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'spots' => $spots
    ]);
} catch (Exception $e) {
    error_log("Spot Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
