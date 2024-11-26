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

    // 調試輸出
    error_log('Received POST data: ' . print_r($_POST, true));
    error_log('Received FILES data: ' . print_r($_FILES, true));

    // 驗證必要欄位
    $requiredFields = [
        'application_id',
        'activity_name',
        'title',
        'start_date',
        'end_date',
        'spot_options'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("必填欄位 {$field} 為空");
        }
    }

    // 日期驗證
    $today = new DateTime();
    $today->setTime(0, 0, 0); // 設置時間為當天 00:00:00
    
    $startDate = new DateTime($_POST['start_date']);
    $endDate = new DateTime($_POST['end_date']);
    
    // 比較日期
    if ($startDate <= $today) {
        throw new Exception('開始日期必須大於今天');
    }
    
    if ($endDate < $startDate) {
        throw new Exception('結束日期必須大於等於開始日期');
    }

    // 驗證圖片
    if (!isset($_FILES['images']) || $_FILES['images']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('請上傳活動圖片');
    }

    $db->beginTransaction();

    try {
        // 解析營位選項
        $spot_options = json_decode($_POST['spot_options'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error decoding spot_options: ' . json_last_error_msg());
            throw new Exception('營位選項資料格式錯誤');
        }

        // 新增活動基本資料
        $stmt = $db->prepare("
            INSERT INTO spot_activities (
                owner_id, application_id, activity_name, 
                title, subtitle, description, notice,
                start_date, end_date, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");

        $stmt->execute([
            $_SESSION['owner_id'],
            $_POST['application_id'],
            $_POST['activity_name'],
            $_POST['title'],
            $_POST['subtitle'] ?? null,
            $_POST['description'] ?? null,
            $_POST['notice'] ?? null,
            $_POST['start_date'],
            $_POST['end_date']
        ]);

        $activity_id = $db->lastInsertId();

        // 處理活動圖片
        if (isset($_FILES['images'])) {
            $uploadDir = __DIR__ . '/../../../uploads/activities/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['images']['name'], PATHINFO_EXTENSION);
            $newFileName = $activity_id . '_' . uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $newFileName;

            if (!move_uploaded_file($_FILES['images']['tmp_name'], $uploadFile)) {
                throw new Exception('圖片上傳失敗');
            }

            // 更新活動圖片路徑
            $stmt = $db->prepare("
                UPDATE spot_activities 
                SET main_image = ? 
                WHERE activity_id = ?
            ");
            $stmt->execute([$newFileName, $activity_id]);
        }

        // 新增活動營位選項
        $stmt = $db->prepare("
            INSERT INTO activity_spot_options (
                activity_id, spot_id, application_id,
                price, max_quantity, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($spot_options as $index => $option) {
            $stmt->execute([
                $activity_id,
                $option['spot_id'],
                $_POST['application_id'],
                $option['price'],
                $option['max_quantity'],
                $index + 1
            ]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => '活動建立成功',
            'activity_id' => $activity_id
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Activity Creation Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'today' => $today->format('Y-m-d'),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date']
        ]
    ]);
    exit;
}
