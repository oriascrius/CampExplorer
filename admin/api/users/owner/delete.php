<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    toggleOwnerStatus();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function toggleOwnerStatus()
{
    global $db;
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('無效的營主ID');
        }

        $db->beginTransaction();

        // 檢查營主是否存在
        $check_stmt = $db->prepare("SELECT id, status FROM owners WHERE id = ?");
        $check_stmt->execute([$id]);
        $owner = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$owner) {
            throw new Exception('找不到該營主');
        }

        // 切換狀態
        $new_status = $owner['status'] ? 0 : 1;
        $action = $new_status ? '啟用' : '停用';
        
        $stmt = $db->prepare("UPDATE owners SET status = ? WHERE id = ?");
        if (!$stmt->execute([$new_status, $id])) {
            throw new Exception($action . '營主失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '已成功' . $action . '該營主',
            'data' => ['status' => $new_status]
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw new Exception('操作失敗：' . $e->getMessage());
    }
}