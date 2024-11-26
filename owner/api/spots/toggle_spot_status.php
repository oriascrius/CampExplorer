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

    if (!isset($_POST['spot_id']) || !isset($_POST['is_active'])) {
        throw new Exception('缺少必要參數');
    }

    $spot_id = $_POST['spot_id'];
    $is_active = $_POST['is_active'];

    // 验证营位所有权
    global $db;
    $check_sql = "SELECT csa.spot_id, ca.status AS application_status
                  FROM camp_spot_applications csa
                  JOIN camp_applications ca ON csa.application_id = ca.application_id
                  WHERE csa.spot_id = :spot_id 
                  AND ca.owner_id = :owner_id";

    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([
        ':spot_id' => $spot_id,
        ':owner_id' => $owner_id
    ]);

    $spot = $check_stmt->fetch();
    if (!$spot) {
        throw new Exception('無權限更新此營位');
    }

    if ((int)$spot['application_status'] !== 1) {
        throw new Exception('只有審核通過的營地才能更新營位狀態');
    }

    // 更新营位状态
    $update_sql = "UPDATE camp_spot_applications 
                   SET status = :status 
                   WHERE spot_id = :spot_id";

    $update_stmt = $db->prepare($update_sql);
    $result = $update_stmt->execute([
        ':status' => $is_active,
        ':spot_id' => $spot_id
    ]);

    if (!$result) {
        throw new Exception('更新失敗');
    }

    echo json_encode([
        'success' => true,
        'message' => '狀態更新成功'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
