<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $owner_id = $_SESSION['owner_id'] ?? null;
    if (!$owner_id) {
        throw new Exception('未登入');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['spot_id'])) {
        throw new Exception('缺少必要參數');
    }

    $spot_id = $data['spot_id'];

    // 驗證營位所有權
    global $db;
    $sql = "SELECT 
                csa.spot_id,
                csa.name,
                csa.capacity,
                csa.price,
                csa.description,
                csa.status,
                ca.status AS application_status
            FROM camp_spot_applications csa
            JOIN camp_applications ca ON csa.application_id = ca.application_id
            WHERE csa.spot_id = :spot_id 
            AND ca.owner_id = :owner_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':spot_id' => $spot_id,
        ':owner_id' => $owner_id
    ]);

    $spot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spot) {
        throw new Exception('找不到營位資料或無權限查看');
    }

    echo json_encode([
        'success' => true,
        'spot' => $spot
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
