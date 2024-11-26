<?php
require_once __DIR__ . "../../../../camping_db.php";

// 获取 JSON 数据
$input = json_decode(file_get_contents('php://input'), true);

// 检查 JSON 是否有效
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["status" => 0, "message" => "无效的 JSON 数据"]);
    exit;
}

// 检查是否提供了 'id' 字段
if (!isset($input['id'])) {
    echo json_encode(["status" => 0, "message" => "ID 未提供"]);
    exit;
}

$id = intval($input['id']); // 确保 ID 是整数

// 定义 SQL 查询语句
$sql = "UPDATE coupons SET status = 0 WHERE id = ?";

try {
    // 准备 SQL 语句
    $stmt = $db->prepare($sql);

    // 执行语句
    if ($stmt->execute([$id])) {
        echo json_encode(["status" => 1, "message" => "刪除成功"]);
    } else {
        echo json_encode(["status" => 0, "message" => "刪除失敗"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => 0, "message" => "查询失败：" . $e->getMessage()]);
    exit;
}

// 关闭数据库连接
$db = null;
?>
