<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    updateOwner();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateOwner() {
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        throw new Exception('缺少必要參數');
    }

    if (!filter_var($data['id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的營主ID');
    }

    $db->beginTransaction();
    try {
        $check_exist = $db->prepare("SELECT * FROM owners WHERE id = ?");
        $check_exist->execute([$data['id']]);
        $owner = $check_exist->fetch(PDO::FETCH_ASSOC);
        
        if (!$owner) {
            throw new Exception('找不到該營主');
        }

        $updates = [];
        $params = [];

        if (isset($data['name'])) {
            if (mb_strlen($data['name']) > 50) {
                throw new Exception('營主姓名不能超過50個字符');
            }
            $updates[] = "name = ?";
            $params[] = trim($data['name']);
        }

        if (isset($data['company_name'])) {
            if (mb_strlen($data['company_name']) > 100) {
                throw new Exception('公司名稱不能超過100個字符');
            }
            $updates[] = "company_name = ?";
            $params[] = trim($data['company_name']);
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('無效的電子郵件格式');
            }
            $check_stmt = $db->prepare("SELECT id FROM owners WHERE email = ? AND id != ?");
            $check_stmt->execute([trim($data['email']), $data['id']]);
            if ($check_stmt->fetch()) {
                throw new Exception('此信箱已被使用');
            }
            $updates[] = "email = ?";
            $params[] = trim($data['email']);
        }

        if (isset($data['phone'])) {
            $updates[] = "phone = ?";
            $params[] = trim($data['phone']);
        }

        if (isset($data['address'])) {
            if (mb_strlen($data['address']) > 200) {
                throw new Exception('地址不能超過200個字符');
            }
            $updates[] = "address = ?";
            $params[] = trim($data['address']);
        }

        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = intval($data['status']);
        }

        if (empty($updates)) {
            throw new Exception('沒有要更新的資料');
        }

        $params[] = $data['id'];
        $sql = "UPDATE owners SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new Exception('更新失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => array_merge($owner, array_intersect_key($data, $owner))
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Update owner error: ' . $e->getMessage());
        throw $e;
    }
}