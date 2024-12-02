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
    
    // 檢查對應營地的狀態
    $checkCampSQL = "SELECT ca.status as camp_status
                     FROM camp_applications ca
                     JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
                     WHERE csa.spot_id = :spot_id";
    
    $checkStmt = $db->prepare($checkCampSQL);
    $checkStmt->execute([':spot_id' => $spotId]);
    $campResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($campResult) {
        // 如果營地狀態是未通過(2)，則營位也必須是未通過
        if ($campResult['camp_status'] == 2) {
            $status = 2;
        }
        // 如果營地狀態是待審核(0)，則營位不能被審核通過
        if ($campResult['camp_status'] == 0 && $status == 1) {
            throw new Exception('營地尚未審核通過，無法核准營位申請');
        }
    }

    $sql = "UPDATE camp_spot_applications 
            SET status = :status
            WHERE spot_id = :spot_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':status' => $status,
        ':spot_id' => $spotId
    ]);

    if ($stmt->rowCount() > 0) {
        $message = $campResult['camp_status'] == 2 ? 
            '由於營地申請未通過，營位狀態已自動設為未通過' : 
            '更新成功';
            
        echo json_encode(['success' => true, 'message' => $message]);
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