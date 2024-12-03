<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    createOwner();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createOwner() {
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 基本驗證
    if (!$data) {
        throw new Exception('無效的請求數據');
    }

    $required_fields = ['email', 'password', 'name', 'company_name'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            if ($field == 'name') {
                throw new Exception('名字為必填欄位');
            } elseif ($field == 'company_name') {
                throw new Exception('公司名稱為必填欄位');
            } elseif ($field == 'password') {
                throw new Exception('密碼為必填欄位');
            }
            else {
                throw new Exception($field . ' 為必填欄位');
            }
        }
    }

    // 欄位格式驗證
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('無效的電子郵件格式');
    }

    if (mb_strlen($data['name']) > 50) {
        throw new Exception('營主姓名不能超過50個字符');
    }

    if (mb_strlen($data['company_name']) > 100) {
        throw new Exception('公司名稱不能超過100個字符');
    }

    $db->beginTransaction();
    try {
        // 檢查信箱是否重複
        $check_stmt = $db->prepare("SELECT id FROM owners WHERE email = ?");
        $check_stmt->execute([trim($data['email'])]);
        if ($check_stmt->fetch()) {
            throw new Exception('此信箱已被使用');
        }
        $hashed_password = md5($data['password']);
        $stmt = $db->prepare("INSERT INTO owners (email, password, name, company_name, phone, 
            address, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
        $result = $stmt->execute([
            trim($data['email']),
            $hashed_password,
            trim($data['name']),
            trim($data['company_name']),
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 1
        ]);

        if (!$result) {
            throw new Exception('新增營主失敗');
        }

        $owner_id = $db->lastInsertId();

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '新增營主成功',
            'data' => array_merge($data, ['id' => $owner_id])
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Create owner error: ' . $e->getMessage());
        throw $e;
    }
}