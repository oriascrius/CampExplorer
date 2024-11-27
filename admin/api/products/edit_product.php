<?php
require_once __DIR__ . "../../../../camping_db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $status = $_POST['status'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];

    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, status = ? ,category_id = ?,subcategory_id = ? WHERE id = ?");
        $stmt->execute([$name, $price, $stock, $status, $category_id, $subcategory_id, $id]);

        echo json_encode(["success" => true, "message" => "更新成功"]);


        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "更新失敗：" . $e->getMessage()]);
        exit;
    }
}
