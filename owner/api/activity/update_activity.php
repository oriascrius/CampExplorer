<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    // 獲取 POST 數據
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    if (!isset($input['activity_id'])) {
        throw new Exception('缺少活動 ID');
    }

    require_once __DIR__ . '/../../../camping_db.php';

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

    // 檢查活動是否已開始
    $today = date('Y-m-d');
    if ($activity['start_date'] <= $today) {
        throw new Exception('已開始的活動無法編輯');
    }

    // 驗證必要欄位
    $requiredFields = [
        'activity_name',
        'title',
        'start_date',
        'end_date'
    ];

    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("必填欄位 {$field} 為空");
        }
    }

    // 驗證日期
    if ($input['start_date'] <= $today) {
        throw new Exception('開始日期必須大於今天');
    }
    if ($input['end_date'] < $input['start_date']) {
        throw new Exception('結束日期必須大於等於開始日期');
    }

    $db->beginTransaction();

    // 更新活動基本資料
    $stmt = $db->prepare("
        UPDATE spot_activities 
        SET activity_name = ?,
            title = ?,
            subtitle = ?,
            description = ?,
            notice = ?,
            start_date = ?,
            end_date = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE activity_id = ? 
        AND owner_id = ?
    ");

    $result = $stmt->execute([
        $input['activity_name'],
        $input['title'],
        $input['subtitle'] ?? null,
        $input['description'] ?? null,
        $input['notice'] ?? null,
        $input['start_date'],
        $input['end_date'],
        $input['activity_id'],
        $_SESSION['owner_id']
    ]);

    if (!$result) {
        throw new Exception('更新失敗');
    }

    // 如果有更新營位選項
    if (isset($input['spot_options'])) {
        // 先刪除原有選項
        $stmt = $db->prepare("DELETE FROM activity_spot_options WHERE activity_id = ?");
        $stmt->execute([$input['activity_id']]);

        // 新增更新後的選項
        $stmt = $db->prepare("
            INSERT INTO activity_spot_options (
                activity_id, spot_id, application_id,
                price, max_quantity, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($input['spot_options'] as $index => $option) {
            $stmt->execute([
                $input['activity_id'],
                $option['spot_id'],
                $activity['application_id'],
                $option['price'],
                $option['max_quantity'],
                $index + 1
            ]);
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => '活動更新成功'
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Activity Update Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}