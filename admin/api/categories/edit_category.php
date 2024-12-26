<?php
require_once __DIR__ . "../../../../camping_db.php";
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['category_id'];
    $name = $_POST['category_name'];
    $status = $_POST['category_status'];

    try {
        $stmt = $db->prepare("UPDATE categories SET name = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $status, $id]);

        // 檢查影響的行數
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "更新成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "更新失敗：資料未變更或找不到該類別"]);
        }

        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "更新失敗：" . $e->getMessage()]);
        exit;
    }
}
