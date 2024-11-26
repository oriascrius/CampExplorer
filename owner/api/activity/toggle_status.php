<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    require_once __DIR__ . '/../../../camping_db.php';

    // 獲取 POST 數據
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['activity_id'])) {
        throw new Exception('缺少活動ID');
    }

    // 檢查活動是否存在且屬於該營主
    $stmt = $db->prepare("
        SELECT * FROM spot_activities 
        WHERE activity_id = ? AND owner_id = ?
    ");
    $stmt->execute([$input['activity_id'], $_SESSION['owner_id']]);
    $activity = $stmt->fetch();

    if (!$activity) {
        throw new Exception('活動不存在或無權限');
    }

    // 檢查活動狀態
    $today = date('Y-m-d');
    if ($activity['end_date'] < $today) {
        throw new Exception('已結束的活動無法更改狀態');
    }

    // 切換狀態
    $newStatus = $activity['is_active'] == 0 ? 1 : 0;
    
    $stmt = $db->prepare("
        UPDATE spot_activities 
        SET is_active = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE activity_id = ?
    ");
    
    $stmt->execute([$newStatus, $input['activity_id']]);

    echo json_encode([
        'success' => true,
        'message' => $newStatus == 1 ? '活動已上架' : '活動已下架',
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    error_log("Activity Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
