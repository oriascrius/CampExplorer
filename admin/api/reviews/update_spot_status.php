<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['spot_id']) || !isset($data['status'])) {
        throw new Exception('缺少必要參數');
    }

    $spotId = $data['spot_id'];
    $status = $data['status'];

    global $db;
    $sql = "UPDATE camp_spot_applications 
            SET status = :status
            WHERE spot_id = :spot_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':status' => $status,
        ':spot_id' => $spotId
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '更新成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '無法更新資料']);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}