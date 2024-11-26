<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    // 處理圖片上傳
    $cover_image_path = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_image'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_type = $file['type'];
        
        // 支援的圖片格式和副檔名設定
        $allowed_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/avif'
        ];

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'avif'];

        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 驗證檔案格式
        if (!in_array($file_type, $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            throw new Exception('不支援的檔案格式，僅支援 JPG、PNG 或 AVIF 格式');
        }
        
        if ($file_size > 5242880) { // 5MB
            throw new Exception('圖片不能超過 5MB');
        }

        $upload_dir = __DIR__ . '/../../../uploads/articles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 保留原始檔名，但確保唯一性
        $original_name = pathinfo($file_name, PATHINFO_FILENAME);
        $filename = $original_name . '.' . $file_extension;
        $filepath = $upload_dir . $filename;

        // 如果檔案已存在，則在檔名後加上數字
        $counter = 1;
        while (file_exists($filepath)) {
            $filename = $original_name . '_' . $counter . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            $counter++;
        }

        if (move_uploaded_file($file_tmp, $filepath)) {
            chmod($filepath, 0644);
            $cover_image_path = '/CampExplorer/uploads/articles/' . $filename;
        } else {
            throw new Exception('圖片上傳失敗');
        }
    }

    $sql = "INSERT INTO articles (title, content, cover_image, status, views, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 0, NOW(), NOW())";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $_POST['title'],
        $_POST['content'],
        $cover_image_path,
        $_POST['status'] ?? 1
    ]);

    if (!$result) {
        throw new Exception('新增文章失敗');
    }

    echo json_encode(['success' => true, 'message' => '文章新增成功']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}