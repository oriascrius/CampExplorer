<?php
require_once __DIR__ . "../../../../camping_db.php";
header('Content-Type: application/json');

// 確保接收到圖片 ID
$inputData = json_decode(file_get_contents('php://input'), true);
$imageId = $inputData['image_id'] ?? null;

if (!$imageId) {
    echo json_encode(['success' => false, 'message' => '未提供圖片 ID']);
    exit;
}

// 獲取圖片路徑
$stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = :id");
$stmt->execute([':id' => $imageId]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$image) {
    echo json_encode(['success' => false, 'message' => '找不到該圖片']);
    exit;
}

$imagePath = $image['image_path'];
$filePath = '../../../uploads/products/img/' . $imagePath;

// 刪除圖片檔案
if (file_exists($filePath)) {
    if (unlink($filePath)) {
        // 文件刪除成功
        error_log("檔案刪除成功: " . $filePath);
    } else {
        error_log("檔案刪除失敗: " . $filePath);
        echo json_encode(['success' => false, 'message' => '刪除檔案失敗']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => '檔案不存在']);
    exit;
}

// 從資料庫刪除圖片資料
$stmt = $db->prepare("DELETE FROM product_images WHERE id = :id");
$stmt->execute([':id' => $imageId]);

// 手動檢查圖片是否已經被刪除
$stmt = $db->prepare("SELECT * FROM product_images WHERE id = :id");
$stmt->execute([':id' => $imageId]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);

if ($image) {
    echo json_encode(['success' => false, 'message' => '資料庫操作失敗']);
} else {
    echo json_encode(['success' => true, 'message' => '圖片刪除成功']);
}

exit;
