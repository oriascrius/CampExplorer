<?php
require_once __DIR__ . "../../../../camping_db.php";

$input = json_decode(file_get_contents('php://input'), true); // 获取 POST 数据 并转换为数组

if (!isset($input['id'])) { // 检查是否提供了 ID
    echo json_encode(["status" => 0, "message" => "ID未提供"]);
    exit;
}

$id = intval($input['id']); // 確保 ID 是整數

try {
    // 准备 SQL 查询
    $sql = "SELECT * FROM coupons WHERE id = :id";
    $stmt = $db->prepare($sql); // 使用 PDO 的 prepare 函数
    $stmt->bindParam(':id', $id, PDO::PARAM_INT); // 绑定参数
    $stmt->execute(); // 执行查询

    $user = $stmt->fetch(PDO::FETCH_ASSOC); // 获取查询结果

    if ($user) {
        echo json_encode(["status" => 1, "data" => $user]); // 查询成功
    } else {
        echo json_encode(["status" => 0, "message" => "用户不存在"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => 0, "message" => "查询失败：" . $e->getMessage()]);
}
?>
