<?php
require_once __DIR__ . "../../../../camping_db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'];  // 確保有收到產品 ID
    $uploadDir =  '../../../uploads/products/img'; // 設定上傳目錄

    // 檢查產品 ID 是否存在
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => '產品 ID 未提供']);
        exit;
    }

    // 檢查是否有上傳圖片並且沒有錯誤
    if (!isset($_FILES['new_image']) || $_FILES['new_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '未選擇圖片或圖片上傳失敗']);
        exit;
    }

    // 處理圖片檔案
    $file = $_FILES['new_image'];
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);  // 取得副檔名
    $fileName = uniqid('product_') . '.' . $fileExtension;  // 生成唯一檔名
    $filePath = $uploadDir . '/' . $fileName;  // 檔案儲存路徑

    // 檢查上傳檔案的暫存路徑是否存在
    if (!file_exists($file['tmp_name'])) {
        echo json_encode(['success' => false, 'message' => '暫存檔案不存在']);
        exit;
    }

    // 檢查上傳目錄是否可寫
    if (!is_writable($uploadDir)) {
        echo json_encode(['success' => false, 'message' => '上傳目錄不可寫']);
        exit;
    }

    // 移動檔案至目標位置
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // 新增圖片資訊到資料庫
        try {
            $stmt = $pdo->prepare("
                INSERT INTO product_images (product_id, image_path, is_main, status, created_at)
                VALUES (:product_id, :image_path, 0, 1, NOW())
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':image_path' => $fileName
            ]);

            // 檢查資料庫操作是否成功
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '資料庫操作失敗']);
            }
        } catch (PDOException $e) {
            // 捕捉資料庫錯誤
            echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
            error_log("Database error: " . $e->getMessage());
            exit;
        }
    } else {
        // 檔案移動失敗
        echo json_encode(['success' => false, 'message' => '圖片檔案移動失敗']);
        error_log("File move failed. Temp name: " . $file['tmp_name'] . " Target path: " . $filePath);
    }
} else {
    echo json_encode(['success' => false, 'message' => '無效的請求方式']);
}
