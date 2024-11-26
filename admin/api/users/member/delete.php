
<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    toggleMemberStatus();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function toggleMemberStatus()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的會員ID');
    }

    if (!isset($data['status']) || !in_array($data['status'], [0, 1])) {
        throw new Exception('無效的狀態值');
    }

    $db->beginTransaction();
    try {
        // 檢查會員是否存在
        $check_exist = $db->prepare("SELECT * FROM users WHERE id = ?");
        $check_exist->execute([$data['id']]);
        $member = $check_exist->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            throw new Exception('找不到該會員');
        }

        // 更新會員狀態
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $result = $stmt->execute([$data['status'], $data['id']]);

        if (!$result) {
            throw new Exception('更新狀態失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '會員狀態已更新'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Toggle member status error: ' . $e->getMessage());
        throw $e;
    }
}