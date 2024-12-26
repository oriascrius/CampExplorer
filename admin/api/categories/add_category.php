<?php
require_once __DIR__ . "../../../../camping_db.php";
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['category_name'];
    $status = $_POST['category_status'];

    try {
        // 準備 SQL 插入語句，將主類別名稱、狀態、創建時間插入
        $stmt = $db->prepare("INSERT INTO categories (name, status, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $status]);

        // 檢查插入操作是否成功
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "主類別新增成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "新增主類別失敗"]);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "新增失敗：" . $e->getMessage()]);
        exit;
    }
}
