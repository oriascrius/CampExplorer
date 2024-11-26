<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'category':
            deleteCategory();
            break;
        case 'subcategory':
            deleteSubcategory();
            break;
        default:
            throw new Exception('無效的操作');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function deleteCategory()
{
    global $db;
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('無效的分類ID');
        }

        $db->beginTransaction();

        // 檢查分類是否存在且未被停用
        $check_stmt = $db->prepare("SELECT id FROM categories WHERE id = ? AND status = 1");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('找不到該分類或已被停用');
        }

        // 停用子分類
        $update_sub = $db->prepare("UPDATE subcategories SET status = 0 WHERE category_id = ? AND status = 1");
        $update_sub->execute([$id]);

        // 停用主分類
        $update_main = $db->prepare("UPDATE categories SET status = 0 WHERE id = ?");
        $main_result = $update_main->execute([$id]);

        if ($main_result === false) {
            throw new Exception('停用主分類失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '已成功停用該分類及其相關子分類'
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Delete category error: ' . $e->getMessage());
        throw new Exception('停用失敗：' . $e->getMessage());
    }
}

function deleteSubcategory()
{
    global $db;
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('無效的子分類ID');
    }

    $db->beginTransaction();
    try {
        // 檢查子分類是否存在且未被停用
        $check_stmt = $db->prepare("SELECT id FROM subcategories WHERE id = ? AND status = 1");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('找不到該子分類或已被停用');
        }

        // 更新子分類狀態
        $stmt = $db->prepare("UPDATE subcategories SET status = 0 WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result === false) {
            throw new Exception('停用子分類失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '已成功停用該子分類'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Delete subcategory error: ' . $e->getMessage());
        throw new Exception('停用失敗：' . $e->getMessage());
    }
}
