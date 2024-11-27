<?php
require_once __DIR__ . "../../../../camping_db.php";
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['subcategory_id'];
    $name = $_POST['subcategory_name'];
    $status = $_POST['subcategory_status'];

    try {
        // 確認該次類別是否存在
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM subcategories WHERE id = ?");
        $checkStmt->execute([$id]);

        if ($checkStmt->fetchColumn() == 0) {
            echo json_encode(["success" => false, "message" => "更新失敗：找不到該次類別"]);
            exit;
        }

        // 更新次類別
        $stmt = $db->prepare("UPDATE subcategories SET name = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $status, $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "更新成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "更新失敗：資料未變更"]);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "更新失敗：" . $e->getMessage()]);
        exit;
    }
}
