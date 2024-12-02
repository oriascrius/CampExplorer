<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    // 獲取輸入數據
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('無效的請求數據');
    }

    require_once __DIR__ . '/../../../camping_db.php';

    // 確保沒有活動的交易
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // 開始交易
    $db->beginTransaction();

    try {
        // 檢查活動存在性和權限
        $stmt = $db->prepare("
            SELECT * FROM spot_activities 
            WHERE activity_id = ? AND owner_id = ?
        ");
        $stmt->execute([$input['activity_id'], $_SESSION['owner_id']]);
        $activity = $stmt->fetch();

        if (!$activity) {
            throw new Exception('活動不存在或無權限');
        }

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

        $stmt->execute([
            $input['activity_name'],
            $input['title'],
            $input['subtitle'],
            $input['description'],
            $input['notice'],
            $input['start_date'],
            $input['end_date'],
            $input['activity_id'],
            $_SESSION['owner_id']
        ]);

        // 獲取現有的營位選項
        $stmt = $db->prepare("
            SELECT option_id, spot_id 
            FROM activity_spot_options 
            WHERE activity_id = ?
        ");
        $stmt->execute([$input['activity_id']]);
        $existingOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 更新營位選項
        foreach ($input['spot_options'] as $index => $option) {
            // 查找是否存在對應的選項
            $existingOption = array_filter($existingOptions, function($eo) use ($option) {
                return $eo['spot_id'] == $option['spot_id'];
            });
            
            if ($existingOption) {
                // 更新現有選項
                $existingOption = reset($existingOption);
                $stmt = $db->prepare("
                    UPDATE activity_spot_options 
                    SET price = ?,
                        max_quantity = ?,
                        sort_order = ?
                    WHERE option_id = ?
                    AND activity_id = ?
                ");
                $stmt->execute([
                    $option['price'],
                    $option['max_quantity'],
                    $index + 1,
                    $existingOption['option_id'],
                    $input['activity_id']
                ]);
            } else {
                // 新增選項
                $stmt = $db->prepare("
                    INSERT INTO activity_spot_options (
                        activity_id, spot_id, application_id,
                        price, max_quantity, sort_order
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
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

        // 刪除不再使用且沒有訂單關聯的選項
        $newSpotIds = array_column($input['spot_options'], 'spot_id');
        if (!empty($newSpotIds)) {
            $placeholders = str_repeat('?,', count($newSpotIds) - 1) . '?';
            $stmt = $db->prepare("
                DELETE FROM activity_spot_options 
                WHERE activity_id = ? 
                AND spot_id NOT IN ({$placeholders})
                AND option_id NOT IN (
                    SELECT DISTINCT option_id 
                    FROM bookings
                )
            ");
            $params = array_merge([$input['activity_id']], $newSpotIds);
            $stmt->execute($params);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => '活動更新成功'
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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