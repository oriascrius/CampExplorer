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

    // 檢查活動是否存在且屬於該營主
    $stmt = $db->prepare("
        SELECT * FROM spot_activities 
        WHERE activity_id = ? AND owner_id = ?
    ");
    $stmt->execute([$_POST['activity_id'], $_SESSION['owner_id']]);
    $activity = $stmt->fetch();

    if (!$activity) {
        throw new Exception('活動不存在或無權限');
    }

    // 檢查活動是否已開始
    $today = date('Y-m-d');
    if ($activity['start_date'] <= $today) {
        throw new Exception('已開始的活動無法刪除');
    }

    $db->beginTransaction();

    // 刪除相關資料
    // 1. 刪除活動圖片
    $stmt = $db->prepare("DELETE FROM activity_images WHERE activity_id = ?");
    $stmt->execute([$_POST['activity_id']]);

    // 2. 刪除活動營位選項
    $stmt = $db->prepare("DELETE FROM activity_spot_options WHERE activity_id = ?");
    $stmt->execute([$_POST['activity_id']]);

    // 3. 刪除活動主表資料
    $stmt = $db->prepare("DELETE FROM spot_activities WHERE activity_id = ?");
    $stmt->execute([$_POST['activity_id']]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => '活動刪除成功'
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Activity Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}