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

    global $db;

    // 驗證營位所有權和審核狀態
    $check_sql = "SELECT csa.status, ca.status AS application_status
    FROM camp_spot_applications csa
    JOIN camp_applications ca ON csa.application_id = ca.application_id
    WHERE csa.spot_id = :spot_id 
    AND ca.owner_id = :owner_id";

    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([
        ':spot_id' => $data['spot_id'],
        ':owner_id' => $owner_id
    ]);

    $spot = $check_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$spot) {
        throw new Exception('無權限更新此營位');
    }

    if ((int)$spot['application_status'] !== 1) {
        throw new Exception('只有審核通過的營地才能編輯營位');
    }

    // 更新營位資訊
    $sql = "UPDATE camp_spot_applications 
            SET name = :name,
                capacity = :capacity,
                price = :price,
                description = :description
            WHERE spot_id = :spot_id";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':name' => $data['spot_name'],
        ':capacity' => intval($data['capacity']),
        ':price' => floatval($data['price']),
        ':description' => $data['description'],
        ':spot_id' => $data['spot_id']
    ]);

    if (!$result) {
        throw new Exception('更新失敗');
    }

    echo json_encode([
        'success' => true,
        'message' => '更新成功'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
