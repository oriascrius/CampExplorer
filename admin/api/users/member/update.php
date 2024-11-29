<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    updateMember();
} catch (Exception $e) {
    http_response_code(400);
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateMember()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
       
        throw new Exception('無效的會員ID1');
    }
    //解釋 filter_var() 函數是用來過濾變數的，本函數可用來過濾設定的變數，如果成功則返回過濾後的數據，如果失敗則返回 false。
    $member_id = (int)$data['id'];
    if ($member_id <= 0) {
        error_log('Invalid member ID: ' . $member_id);
        throw new Exception('無效的會員ID2');
    }

    $db->beginTransaction();
    try {
        // 檢查會員是否存在
        $check_exist = $db->prepare("SELECT * FROM users WHERE id = ?");
        $check_exist->execute([$member_id]);
        $member = $check_exist->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
         
            throw new Exception('找不到該會員');
        }

        // 準備更新欄位
        $updates = [];
        $params = [];

        // 檢查並設置各個欄位的更新
        if (isset($data['name']) && !empty(trim($data['name'])) && mb_strlen($data['name']) <= 50 && preg_match('/^[\p{L}\s]+$/u', $data['name'])) {
            $updates[] = "name = ?";
            $params[] = trim($data['name']);
        } else {
            throw new Exception('姓名不得為空且必須為有效的字符');
        }

        if (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            // 檢查信箱是否重複
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([trim($data['email']), $member_id]);
            if ($check_stmt->fetch()) {
                error_log('Email already in use: ' . $data['email']);
                throw new Exception('此信箱已被使用');
            }
            $updates[] = "email = ?";
            $params[] = trim($data['email']);
        }

        if (isset($data['phone'])) {
            if (!preg_match('/^\+?[0-9]{10,15}$/', trim($data['phone']))) {
                throw new Exception('無效的電話號碼');
            }
            $updates[] = "phone = ?";
            $params[] = trim($data['phone']);
        }

        if (isset($data['birthday'])) {
            $updates[] = "birthday = ?";
            $params[] = $data['birthday'];
        }

        if (isset($data['gender']) && in_array($data['gender'], ['male', 'female', 'other'])) {
            $updates[] = "gender = ?";
            $params[] = $data['gender'];
        }

        if (isset($data['address'])) {
            $updates[] = "address = ?";
            $params[] = trim($data['address']);
        }

        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = intval($data['status']);
        }
        $updates[] = 'updated_at = NOW()';
        if (empty($updates)) {
            error_log('No data to update: ' . json_encode($data));
            throw new Exception('沒有要更新的資料');
        }
        
        // 加入ID參數
        $params[] = $member_id;

        // 執行更新
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            error_log('Update failed for member ID: ' . $member_id);
            throw new Exception('更新失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => array_merge($member, array_intersect_key($data, $member))
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Update member error: ' . $e->getMessage());
        throw $e;
    }
}