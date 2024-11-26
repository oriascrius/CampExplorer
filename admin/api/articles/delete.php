<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    if (!$id) throw new Exception('缺少文章ID');

    // 開始事務
    $db->beginTransaction();

    // 刪除文章
    $sql = "DELETE FROM articles WHERE id = ?";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([$id]);

    if (!$success) {
        throw new Exception('刪除文章失敗');
    }

    // 提交事務
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => '文章已刪除'
    ]);
} catch (Exception $e) {
    // 發生錯誤時回滾事務
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}