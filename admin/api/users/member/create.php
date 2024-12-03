<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');




try {
    createMember();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
}

function createMember() {
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 基本驗證
    if (!$data) {
        throw new Exception('無效的請求數據');
    }

    $required_fields = ['email', 'password', 'name'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception($field . ' 為必填欄位');
        }
    }

    // 欄位格式驗證
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('無效的電子郵件格式');
    }

    if (mb_strlen($data['name']) > 50) {
        throw new Exception('會員名稱不能超過50個字符');
    }

    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $data['password'])) {
        echo json_encode(['success' => false, 'message' => '密碼至少8個字符，且包含至少一個字母和一個數字']);
        return;
    }

    if (isset($data['phone']) && !preg_match('/^\d{10}$/', $data['phone'])) {
        throw new Exception('無效的電話號碼格式');
    }

    $db->beginTransaction();
    try {
        // 檢查信箱是否重複
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([trim($data['email'])]);
        if ($check_stmt->fetch()) {
            throw new Exception('此信箱已被使用');
        }
        $hashed_password = md5($data['password']);
        $stmt = $db->prepare("INSERT INTO users (email, password, name, phone, birthday, 
            gender, address, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $currentTime = date('Y-m-d H:i:s');
        $result = $stmt->execute([
            trim($data['email']),
            $hashed_password,
            trim($data['name']),
            $data['phone'] ?? null,
            $data['birthday'] ?? null,
            $data['gender'] ?? 'other',
            $data['address'] ?? null,
            $data['status'] ?? 1,
            $currentTime, // 設置 created_at 欄位的值
            $currentTime  // 設置 updated_at 欄位的值
        ]);

        if (!$result) {
            throw new Exception('新增會員失敗');
        }

        $member_id = $db->lastInsertId();

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '新增會員成功',
            'data' => array_merge($data, ['id' => $member_id])
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Create member error: ' . $e->getMessage());
        throw $e;
    }
}

