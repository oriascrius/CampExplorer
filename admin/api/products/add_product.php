<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不支援的請求方式']);
    exit;
}

require_once __DIR__ . "../../../../camping_db.php";


// 相對路徑從 admin/api/products 到 uploads/products/img
$uploadDir =  '../../../uploads/products/img';





try {
    // 接收表單數據
    $name = $_POST['name'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $subcategory_id = $_POST['subcategory_id'] ?? null;
    $description = $_POST['description'] ?? null;
    $price = $_POST['price'] ?? null;
    $stock = $_POST['stock'] ?? null;
    $status = $_POST['status'] ?? 1;


    if (!$name || !$category_id || !$price || !$stock) {
        echo json_encode(['success' => false, 'message' => '請填寫必要欄位']);
        exit;
    }

    // 新增商品到 products 表
    $stmt = $db->prepare("
        INSERT INTO products (name, category_id, subcategory_id, description, price, stock, status, created_at)
        VALUES (:name, :category_id, :subcategory_id, :description, :price, :stock, :status, NOW())
    ");
    $stmt->execute([
        ':name' => $name,
        ':category_id' => $category_id,
        ':subcategory_id' => $subcategory_id,
        ':description' => $description,
        ':price' => $price,
        ':stock' => $stock,
        ':status' => $status
    ]);
    $product_id = $db->lastInsertId();

    // 處理主圖片上傳
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $mainImage = $_FILES['main_image'];
        $mainImageExtension = pathinfo($mainImage['name'], PATHINFO_EXTENSION);
        $mainImageName = uniqid('product_', true) . '.' . $mainImageExtension;
        $mainImagePath = $uploadDir . '/' . $mainImageName;
        if (move_uploaded_file($mainImage['tmp_name'], $mainImagePath)) {
            // 新增主圖片到 product_images 表
            $stmt = $db->prepare("
                INSERT INTO product_images (product_id, image_path, is_main, status, created_at)
                VALUES (:product_id, :image_path, 1, 1, NOW())
            ");
            $stmt->execute([
                ':product_id' => $product_id,
                ':image_path' => $mainImageName
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '主圖片上傳失敗']);
            exit;
        }
    }



    // 處理附加圖片上傳
    if (!empty($_FILES['additional_images']['name'][0])) {
        foreach ($_FILES['additional_images']['name'] as $index => $filename) {
            // 檢查檔案是否上傳成功
            if ($_FILES['additional_images']['error'][$index] === UPLOAD_ERR_OK) {
                // 取得檔案的擴展名
                $imageExtension = pathinfo($filename, PATHINFO_EXTENSION);
                // 生成唯一的檔案名稱
                $uniqueImageName = uniqid('product_', true) . '.' . $imageExtension;
                $uploadPath = $uploadDir . '/' . $uniqueImageName;

                // 移動檔案到指定目錄
                if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$index], $uploadPath)) {
                    // 新增附加圖片資料到 product_images 表
                    $stmt = $db->prepare("
                        INSERT INTO product_images (product_id, image_path, is_main, status, created_at)
                        VALUES (:product_id, :image_path, 0, 1, NOW())
                    ");
                    $stmt->execute([
                        ':product_id' => $product_id,
                        ':image_path' => $uniqueImageName
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => '附加圖片上傳失敗']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => '附加圖片上傳錯誤: ' . $_FILES['additional_images']['error'][$index]]);
                exit;
            }
        }
    }

    echo json_encode(['success' => true, 'message' => '商品新增成功']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '伺服器錯誤：' . $e->getMessage()]);
}
