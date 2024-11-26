<?php
// 開啟錯誤報告但不直接輸出到頁面
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 設置錯誤處理器
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});

// 設置異常處理器
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
});
// 確保輸出為 JSON
header('Content-Type: application/json');

try {
    // 檢查是否為 POST 請求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 使用絕對路徑引入資料庫配置
    $basePath = str_replace('\\', '/', dirname(dirname(dirname(dirname(__FILE__)))));
    $dbPath = $basePath . '/camping_db.php';
    
    if (!file_exists($dbPath)) {
        throw new Exception('Database configuration file not found at: ' . $dbPath);
    }
    
    require_once $dbPath;
    
    if (!isset($db)) {
        throw new Exception('Database connection failed');
    }
    
    $pdo = $db;

    // 檢查登入狀態
    session_start();
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('請先登入');
    }

    // 修改上傳目錄路徑
    $uploadPaths = [
        __DIR__ . '/../../../uploads',
        __DIR__ . '/../../../uploads/camps',
        __DIR__ . '/../../../uploads/spots'
    ];

    foreach ($uploadPaths as $path) {
        if (!file_exists($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new Exception("無法創建目錄: $path");
            }
        }
        if (!is_writable($path)) {
            throw new Exception("目錄無寫入權限: $path");
        }
    }

    // 驗證必要欄位
    $requiredFields = ['name', 'owner_name', 'address', 'description'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("必填欄位 {$field} 為空");
        }
    }

    // 驗證圖片上傳
    if (!isset($_FILES['camp_main_image']) || $_FILES['camp_main_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('請上傳營地主要圖片');
    }

    // 開始資料庫交易
    $pdo->beginTransaction();

    try {
        // 插入營地申請資料
        $stmt = $pdo->prepare("
            INSERT INTO camp_applications 
            (owner_id, owner_name, name, address, description, rules, notice, status, operation_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1)
        ");

        $stmt->execute([
            $_SESSION['owner_id'],
            $_POST['owner_name'],
            $_POST['name'],
            $_POST['address'],
            $_POST['description'],
            $_POST['rules'] ?? '',
            $_POST['notice'] ?? ''
        ]);

        $applicationId = $pdo->lastInsertId();

        // 處理圖片上傳
        $fileInfo = $_FILES['camp_main_image'];
        $extension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $newFilename = uniqid('camp_', true) . '.' . $extension;

        // 修改上傳目錄為 CampExplorer/uploads/camps
        $uploadDir = __DIR__ . '/../../../uploads/camps';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

        if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
            throw new Exception('圖片上傳失敗');
        }

        // 只存儲檔名到資料庫
        $stmt = $pdo->prepare("
            INSERT INTO camp_images 
            (application_id, owner_id, image_path) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $applicationId,
            $_SESSION['owner_id'],
            $newFilename
        ]);

        // 處理營位資料
        if (!isset($_POST['spot_types']) || !is_array($_POST['spot_types']) || empty($_POST['spot_types'])) {
            throw new Exception('請至少新增一個營位類型');
        }

        foreach ($_POST['spot_types'] as $index => $spotType) {
            // 驗證必要欄位
            if (empty($spotType['name'])) {
                throw new Exception("第 " . ($index + 1) . " 個營位的名稱不能為空");
            }
            if (!isset($spotType['capacity']) || intval($spotType['capacity']) <= 0) {
                throw new Exception("第 " . ($index + 1) . " 個營位的容納人數必須大於 0");
            }
            if (!isset($spotType['price']) || floatval($spotType['price']) <= 0) {
                throw new Exception("第 " . ($index + 1) . " 個營位的價格必須大於 0");
            }

            // 插入營位資料
            $stmt = $pdo->prepare("
                INSERT INTO camp_spot_applications 
                (application_id, owner_name, name, capacity, price, description, status) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $applicationId,
                $_POST['owner_name'],
                $spotType['name'],
                intval($spotType['capacity']),
                floatval($spotType['price']),
                $spotType['description'] ?? '',
            ]);
            
            $spotId = $pdo->lastInsertId();

            // 處理營位圖片
            $spotImageKey = "spot_images_{$index}";
            if (isset($_FILES[$spotImageKey]) && $_FILES[$spotImageKey]['error'] === UPLOAD_ERR_OK) {
                $spotImage = $_FILES[$spotImageKey];
                
                // 生成唯一檔名
                $extension = pathinfo($spotImage['name'], PATHINFO_EXTENSION);
                $newFilename = 'spot_' . uniqid() . '.' . $extension;
                
                // 設定上傳路徑
                $uploadDir = __DIR__ . '/../../../uploads/spots';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . '/' . $newFilename;
                
                if (move_uploaded_file($spotImage['tmp_name'], $destination)) {
                    // 儲存圖片資訊到資料庫
                    $stmt = $pdo->prepare("
                        INSERT INTO camp_spot_images 
                        (spot_id, owner_id, image_path) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $spotId,
                        $_SESSION['owner_id'],
                        $newFilename
                    ]);
                } else {
                    throw new Exception("營位圖片上傳失敗");
                }
            }
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '申請成功',
            'application_id' => $applicationId
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

