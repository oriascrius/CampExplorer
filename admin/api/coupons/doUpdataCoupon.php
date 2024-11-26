<?php

require_once __DIR__ . "../../../../camping_db.php";

// $input = json_decode(file_get_contents('php://input'), true); 

if (!isset($_POST["id"]) || !isset($_POST["code"]) || !isset($_POST["name"]) || !isset($_POST["discount_type"]) || !isset($_POST["discount_value"]) || !isset($_POST["min_purchase"]) || !isset($_POST["max_discount"]) || !isset($_POST["start_date"]) || !isset($_POST["end_date"])) {
    echo json_encode(["status" => 0, "message" => "資料不完整"]);
    exit;
}


$id = intval($_POST["id"]); // 確保id是整數


try {
    // 确保从 $_POST 获取到所有需要的数据
    $code = $_POST["code"];
    $name = $_POST["name"];
    $discount_type = $_POST["discount_type"];
    $discount_value = $_POST["discount_value"];
    $min_purchase = $_POST["min_purchase"];
    $max_discount = $_POST["max_discount"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $id = $_POST["id"];  // 确保 id 也在请求中

    $sql = "UPDATE coupons SET code = ?, name = ?, discount_type = ?, discount_value = ?, min_purchase = ?, max_discount = ?, start_date = ?, end_date = ? WHERE id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt->execute([$code, $name, $discount_type, $discount_value, $min_purchase, $max_discount, $start_date, $end_date, $id])) {
        echo json_encode(["status" => 1, "message" => "資料更新成功"]);
    } else {
        echo json_encode(["status" => 0, "message" => "資料更新失敗"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => 0, "message" => "查询失败：" . $e->getMessage()]);
    exit;
}




// $stmt->close();
// $conn->close();
