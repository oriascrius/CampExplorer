<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    // 修改數據獲取方式
    $input = $_POST;
    
    // 解析 JSON 格式的營位選項
    if (isset($_POST['spot_options'])) {
        $input['spot_options'] = json_decode($_POST['spot_options'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('營位選項格式錯誤');
        }
    }

    // 記錄接收到的數據（偵錯用）
    error_log('Received input: ' . print_r($input, true));

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

    // 移除時間相關的驗證，只保留基本驗證
    if ($input['end_date'] < $input['start_date']) {
        throw new Exception('結束日期必須大於等於開始日期');
    }

    // 驗證必要欄位
    $requiredFields = [
        'activity_name',
        'title',
        'start_date',
        'end_date'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            throw new Exception("必填欄位 {$field} 為空");
        }
    }

    // 驗證營位選項
    if (!isset($input['spot_options']) || !is_array($input['spot_options']) || empty($input['spot_options'])) {
        throw new Exception('請至少設定一個營位選項');
    }

    foreach ($input['spot_options'] as $index => $option) {
        // 驗證必要欄位
        if (!isset($option['spot_id']) || !isset($option['price']) || !isset($option['max_quantity'])) {
            throw new Exception('營位選項資料不完整');
        }

        // 驗證價格和數量
        if (!is_numeric($option['price']) || $option['price'] <= 0) {
            throw new Exception('營位價格必須大於 0');
        }
        if (!is_numeric($option['max_quantity']) || $option['max_quantity'] <= 0) {
            throw new Exception('營位數量必須大於 0');
        }

        // 驗證營位是否屬於該活動
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM activity_spot_options 
            WHERE activity_id = ? AND spot_id = ?
        ");
        $stmt->execute([$input['activity_id'], $option['spot_id']]);
        if ($stmt->fetchColumn() === 0) {
            throw new Exception('無效的營位選項');
        }
    }

    $db->beginTransaction();

    try {
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
            throw new Exception('更新活動基本資料失敗');
        }

        // 更新營位選項
        $stmt = $db->prepare("DELETE FROM activity_spot_options WHERE activity_id = ?");
        $stmt->execute([$input['activity_id']]);

        $stmt = $db->prepare("
            INSERT INTO activity_spot_options (
                activity_id, spot_id, application_id,
                price, max_quantity, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($input['spot_options'] as $index => $option) {
            $result = $stmt->execute([
                $input['activity_id'],
                $option['spot_id'],
                $activity['application_id'],
                $option['price'],
                $option['max_quantity'],
                $index + 1
            ]);
            
            if (!$result) {
                throw new Exception('更新營位選項失敗');
            }
        }

        // 處理圖片上傳（如果有）
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // 處理圖片上傳邏輯...
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => '活動更新成功'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Activity Update Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}