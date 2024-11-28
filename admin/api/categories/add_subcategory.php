<?php
require_once __DIR__ . "../../../../camping_db.php";
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category_id'];
    $name = $_POST['subcategory_name'];
    $status = $_POST['subcategory_status'];

    try {
        // 準備插入資料的 SQL 語句
        $stmt = $db->prepare("INSERT INTO subcategories (category_id, name, status, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$categoryId, $name, $status]);

        // 檢查插入是否成功
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "次類別新增成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "次類別新增失敗"]);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "新增失敗：" . $e->getMessage()]);
        exit;
    }
}
